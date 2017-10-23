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

    private $settings;
    private $httpClient;
    private $storage;

    private $useCache;
    private $cachePool;
    private $updateLockKey;

    // TODO: move scalars to settings object?
    public function __construct(
        Settings $settings,
        HttpClient $httpClient,
        Storage $storage,
        CacheItemPoolInterface $cachePool = null
    ) {
        $this->settings = $settings;
        $this->httpClient = $httpClient;
        $this->storage = $storage;

        if (is_null($cachePool)) {
            $this->useCache = false;
        } else {
            $this->useCache = true;
            $this->cachePool = $cachePool;
        }
        $this->updateLockKey = "UPDATE_LOCK_{$this->settings->getAppName()}";
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
            $this->settings->getUnleashHost() . self::ENDPOINT_FEATURES,
            [
                "If-None-Match" => $eTag,
                "Content-Type" => "Application/Json",
                "UNLEASH-APPNAME" => $this->settings->getAppName(),
                "UNLEASH-INSTANCEID" => $this->settings->getInstanceId(),
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

        $json = $response->getBody()->getContents();
        $data = Json::decode($json, true);
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
        return $this->storage->getLastUpdated() > (time() - $this->settings->getUpdateInterval());
    }

    private function ignoreOrFail()
    {
        if ($this->storage->hasData()) {
            return;
        }
        throw new \Exception("Could not load data from anywhere (server, backup (or cache))");
    }
}
