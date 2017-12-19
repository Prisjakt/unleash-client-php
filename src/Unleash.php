<?php

namespace Prisjakt\Unleash;

use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use League\Flysystem\FilesystemInterface;
use Prisjakt\Unleash\Cache\CacheInterface;
use Prisjakt\Unleash\Feature\Processor;
use Prisjakt\Unleash\Helpers\Json;
use Prisjakt\Unleash\Metrics\Reporter;
use Prisjakt\Unleash\Metrics\Storage\StorageInterface;
use Prisjakt\Unleash\Storage\BackupStorage;
use Prisjakt\Unleash\Storage\CachedStorage;
use Prisjakt\Unleash\Strategy;

// TODO: add (optional) logging? (e.g. if feature does not exist, strategy not implemented, can't connect to server)
class Unleash
{
    const ENDPOINT_REGISTER = "/api/client/register";

    private $settings;
    private $featureProcessor;
    private $repository;
    private $httpClient;
    private $metricsStorage;
    private $reporter;
    private $startTime;

    public function __construct(
        Settings $settings,
        array $strategies,
        HttpClient $httpClient,
        FilesystemInterface $filesystem = null,
        CacheInterface $cache = null,
        StorageInterface $metricsStorage = null,
        Reporter $reporter = null
    ) {
        $this->settings = $settings;
        $this->httpClient = $httpClient;

        $strategyRepository = new Strategy\Repository($strategies);
        $this->featureProcessor = new Processor($strategyRepository);

        $storage = new BackupStorage($settings->getAppName(), $filesystem);

        if ($cache !== null) {
            $storage = new CachedStorage($settings->getAppName(), $cache, $storage);
        }

        $this->repository = new Repository(
            $settings,
            $storage,
            $httpClient,
            $cache
        );

        if ($this->settings->getRegisterOnInstantiation()) {
            $this->register($strategyRepository->getNames());
        }
        $this->metricsStorage = $metricsStorage;
        $this->reporter = $reporter;
        $this->startTime = time();
    }

    public function __destruct()
    {
        if (!($this->metricsStorage && $this->reporter)) {
            return;
        }

        $allFeatures = $this->repository->getAll();
        if (is_null($allFeatures) || empty($allFeatures)) {
            return;
        }

        $featureStats = [];
        foreach (array_keys($allFeatures) as $feature) {
            $featureStats[$feature] = $this->metricsStorage->get($feature, true);
        }
        $this->reporter->report($this->startTime, $featureStats);
    }

    public function isEnabled(string $key, array $context = [], bool $default = false): bool
    {
        $this->fetch();

        if (!$this->repository->has($key)) {
            return $default;
        }

        $feature = $this->repository->get($key);

        $result = $this->featureProcessor->process($feature, $context, $default);
        if ($this->metricsStorage) {
            $this->metricsStorage->add($feature->getName(), $result);
        }
        return $result;
    }

    public function fetch($force = false)
    {
        $this->repository->fetch($force);
    }

    public function register(array $implementedStrategies)
    {
        $request = new Request(
            "post",
            $this->settings->getUnleashHost() . self::ENDPOINT_REGISTER,
            ["Content-Type" => "Application/Json"],
            Json::encode([
                "appName" => $this->settings->getAppName(),
                "instanceId" => $this->settings->getInstanceId(),
                "strategies" => $implementedStrategies,
                "started" => date("c"),
                "interval" => $this->settings->getDataMaxAge(),
            ])
        );

        try {
            $this->httpClient->sendRequest($request);
        } catch (\Exception $e) {
            // TODO: We should really catch more specific exceptions but every adapter throws different exceptions.
        }
    }
}
