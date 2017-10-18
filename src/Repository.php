<?php

namespace Prisjakt\Unleash;

use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use Prisjakt\Unleash\Feature\Feature;
use Prisjakt\Unleash\Helpers\Json;
use Psr\Cache\CacheItemPoolInterface;

class Repository
{
    const ENDPOINT_FEATURES = "/api/features";
    const ENDPOINT_REGISTER = "/api/client/register";

    private $unleashHost;
    private $appName;
    private $instanceId;
    private $httpClient;
    private $storage;

    private $useCache;
    private $cachePool;
    private $updateInterval;
    private $updateLockKey;

    // TODO: move scalars to settings object?
    public function __construct(
        string $unleashHost,
        string $appName,
        string $instanceId,
        HttpClient $httpClient,
        Storage $storage,
        int $updateInterval = 15,
        CacheItemPoolInterface $cachePool = null
    ) {
        $this->appName = $appName;
        $this->instanceId = $instanceId;
        $this->httpClient = $httpClient;
        $this->storage = $storage;

        if (is_null($cachePool)) {
            $this->useCache = false;
        } else {
            $this->useCache = true;
            $this->cachePool = $cachePool;
        }
        $this->unleashHost = $unleashHost;
        $this->updateInterval = $updateInterval;
        $this->updateLockKey = "UPDATE_LOCK_{$this->appName}";
    }

    public function fetch()
    {
        if ($this->isStorageFresh()) {
            return;
        }

        if ($this->isUpdateLocked()) {
            return;
        }

        $this->lockUpdates();

        $eTag = $this->storage->getETag() ?? "";
        $request = new Request(
            "get",
            $this->unleashHost . self::ENDPOINT_FEATURES,
            [
                "If-None-Match" => $eTag,
                "Content-Type" => "Application/Json",
                "UNLEASH-APPNAME" => $this->appName,
                "UNLEASH-INSTANCEID" => $this->instanceId,
            ]
        );

        try {
            $response = $this->httpClient->sendRequest($request);
            $statusCode = $response->getStatusCode();
            $eTag = implode(", ", $response->getHeader("Etag"));
        } catch (\Exception $e) {
            $this->ignoreOrFail();
            return;
        }

        if ($statusCode === 304) {
            if ($this->storage->getETag() === $eTag) {
                $this->storage->resetLastUpdated();
                return;
            }
        }

        if ($statusCode < 200 || $statusCode > 299) {
            $this->ignoreOrFail();
            return;
        }

        $data = Json::decode($response->getBody()->getContents(), true);
        $this->storage->reset($data["features"], $eTag);
        $this->releaseUpdateLock();
    }

    public function has(string $key): bool
    {
        return $this->storage->has($key);
    }

    public function get(string $key): Feature
    {
        return $this->storage->get($key);
    }

    private function isUpdateLocked(): bool
    {
        if (!$this->useCache) {
            return false;
        }

        $lockItem = $this->cachePool->getItem($this->updateLockKey);
        if (!$lockItem->isHit()) {
            return false;
        }

        return $lockItem->get() === true;
    }

    private function lockUpdates()
    {
        if (!$this->useCache) {
            return;
        }

        $lockItem = $this->cachePool->getItem($this->updateLockKey);

        $lockItem->set(true);
        $this->cachePool->save($lockItem);
    }

    private function releaseUpdateLock()
    {
        if (!$this->useCache) {
            return;
        }

        if ($this->cachePool->hasItem($this->updateLockKey)) {
            $this->cachePool->deleteItem($this->updateLockKey);
        }
    }

    private function isStorageFresh(): bool
    {
        return $this->storage->getLastUpdated() > (time() - $this->updateInterval);
    }

    private function ignoreOrFail()
    {
        if ($this->storage->hasData()) {
            return;
        }
        throw new \Exception("Could not load data from anywhere (server, backup (or cache))");
    }
}
