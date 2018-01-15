<?php

namespace Prisjakt\Unleash;

use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use League\Flysystem\FilesystemInterface;
use Prisjakt\Unleash\Cache\CacheInterface;
use Prisjakt\Unleash\Feature\Processor;
use Prisjakt\Unleash\Helpers\Json;
use Prisjakt\Unleash\LoaderStrategy\Awareness\ContextAware;
use Prisjakt\Unleash\LoaderStrategy\DefaultLoader;
use Prisjakt\Unleash\LoaderStrategy\LoaderStrategyInterface;
use Prisjakt\Unleash\Metrics\Reporter;
use Prisjakt\Unleash\Metrics\Storage\StorageInterface;
use Prisjakt\Unleash\Strategy;

// TODO: add (optional) logging? (e.g. if feature does not exist, strategy not implemented, can't connect to server)
class Unleash
{
    const ENDPOINT_REGISTER = "/api/client/register";

    private $settings;
    private $featureProcessor;
    private $httpClient;
    private $metricsStorage;
    private $reporter;
    private $startTime;
    /** @var  LoaderStrategyInterface */
    private $loaderStrategy;
    private $cache;
    private $filesystem;
    private $dataStorage;
    private $dataBackend;

    public function __construct(
        Settings $settings,
        array $strategies,
        HttpClient $httpClient,
        FilesystemInterface $filesystem = null,
        CacheInterface $cache = null,
        LoaderStrategyInterface $loaderStrategy = null,
        StorageInterface $metricsStorage = null,
        Reporter $reporter = null
    ) {
        $this->settings = $settings;

        $this->httpClient = $httpClient;
        $this->cache = $cache;
        $this->filesystem = $filesystem;

        $this->metricsStorage = $metricsStorage;
        $this->reporter = $reporter;

        $this->initLoaderStrategy($loaderStrategy);
        $this->dataBackend = (new Backend())
            ->setHttp($this->settings, $this->httpClient)
            ->setCache($this->cache)
            ->setBackup($this->filesystem, $this->settings->getAppName());

        $this->startTime = time();

        $strategyRepository = new Strategy\Repository($strategies);
        $this->featureProcessor = new Processor($strategyRepository);

        if ($this->settings->getRegisterOnInstantiation()) {
            $this->register($strategyRepository->getNames());
        }
    }

    public function __destruct()
    {
        if (!($this->metricsStorage && $this->reporter && $this->dataStorage)) {
            return;
        }

        $allFeatures = $this->dataStorage->getFeatures();
        if (is_null($allFeatures) || empty($allFeatures)) {
            return;
        }

        $featureStats = [];
        foreach (array_keys($allFeatures) as $feature) {
            $stats = $this->metricsStorage->get($feature, true);

            if (empty($stats) || ($stats["yes"] === 0 && $stats["no"] === 0)) {
                continue;
            }

            $featureStats[$feature] = $stats;
        }
        $this->reporter->report($this->startTime, $featureStats);
    }

    public function isEnabled(string $key, array $context = [], bool $default = false): bool
    {
        $this->fetch();

        if (!$this->dataStorage->has($key)) {
            return $default;
        }

        $feature = $this->dataStorage->get($key);

        $result = $this->featureProcessor->process($feature, $context, $default);
        if ($this->metricsStorage) {
            $this->metricsStorage->add($feature->getName(), $result);
        }
        return $result;
    }

    public function fetch(bool $force = false)
    {
        if ($force || $this->dataStorage === null) {
            $this->dataStorage = $this->loaderStrategy->load($this->dataBackend);
        }
    }

    /**
     * Short circuit unleash
     * sets dataStorage to an empty dataStorage instance
     * which will result in isEnabled calls return default for everything.
     */
    public function shortCircuit()
    {
        $this->dataStorage = new DataStorage();
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

    private function initLoaderStrategy(LoaderStrategyInterface $loaderStrategy = null)
    {
        if ($loaderStrategy === null) {
            $loaderStrategy = new DefaultLoader();
        }
        $this->loaderStrategy = $loaderStrategy;

        if ($this->loaderStrategy instanceof ContextAware) {
            $this->loaderStrategy->setSettings($this->settings);
            if ($this->cache !== null) {
                $this->loaderStrategy->setCache($this->cache);
            }
        }
    }
}
