<?php

namespace Prisjakt\Unleash;

use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use League\Flysystem\FilesystemInterface;
use Prisjakt\Unleash\Feature\Processor;
use Prisjakt\Unleash\Helpers\Json;
use Prisjakt\Unleash\Strategy;
use Psr\Cache\CacheItemPoolInterface;
use Prisjakt\Unleash\Storage\BackupStorage;
use Prisjakt\Unleash\Storage\CachedStorage;

// TODO: add (optional) logging? (e.g. if feature does not exist/strategy not implemented/cant connect to server)
class Unleash
{
    const ENDPOINT_REGISTER = "/api/client/register";

    private $settings;
    private $featureProcessor;
    private $repository;
    private $httpClient;

    public function __construct(
        Settings $settings,
        array $strategies,
        HttpClient $httpClient,
        FilesystemInterface $filesystem = null,
        CacheItemPoolInterface $cacheItemPool = null
    ) {
        $this->settings = $settings;
        $this->httpClient = $httpClient;

        $strategyRepository = new Strategy\Repository($strategies);
        $this->featureProcessor = new Processor($strategyRepository);

        $storage = new BackupStorage($settings->getAppName(), $filesystem);

        if ($cacheItemPool !== null) {
            $storage = new CachedStorage($settings->getAppName(), $cacheItemPool, $storage);
        }

        $this->repository = new Repository(
            $settings,
            $storage,
            $httpClient,
            $cacheItemPool
        );

        if ($this->settings->getRegisterOnInstantiation()) {
            $this->register($strategyRepository->getNames());
        }
    }

    public function isEnabled(string $key, array $context = [], bool $default = false): bool
    {
        $this->repository->fetch();

        if (!$this->repository->has($key)) {
            return $default;
        }

        $feature = $this->repository->get($key);

        return $this->featureProcessor->process($feature, $context, $default);
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
                "started" => time(),
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
