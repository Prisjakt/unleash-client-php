<?php

namespace Prisjakt\Unleash;

use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use Prisjakt\Unleash\Feature\Feature;
use Prisjakt\Unleash\Helpers\Json;
use Prisjakt\Unleash\Storage\StorageInterface;
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
        StorageInterface $storage,
        HttpClient $httpClient = null,
        CacheItemPoolInterface $cachePool = null
    ) {
        $this->settings = $settings;

        if ($httpClient === null && $settings->shouldRefreshFromServerIfStale()) {
            throw new \InvalidArgumentException(
                "Settings specify that server should be used for refreshing data but no httpClient has been passed"
            );
        }
        $this->httpClient = $httpClient;
        $this->storage = $storage;
        try {
            $this->storage->load();
        } catch (\Exception $e) {
            // pass
        }


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

        // TODO: improbable but possible: Another client has updated the cache since the current client fetched.
        // TODO: a solution to this (if we really want to avoid another call to the server)
        // TODO: would be to re-fetch and check what's currently stored in the cache.
        // TODO: This should not be a problem for regular short-lived request-response processes.
        // TODO: Long-running processes however are more likely to encounter this issue.
        // TODO: If long running processes become a common thing we should reconsider adding the cache reload solution.

        if (!$this->settings->shouldRefreshFromServerIfStale()) {
            if (!$this->storage->isHit()) {
                throw new \Exception("No data in cache and server refresh is disabled");
            }
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
                $this->storage->save();
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
        $this->storage->save();
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
        return $this->storage->getLastUpdated() > (microtime(true) - $this->settings->getDataMaxAge());
    }

    private function ignoreOrFail()
    {
        if ($this->storage->isHit()) {
            return;
        }
        throw new \Exception("Could not load data from anywhere (server, backup (or cache))");
    }
}
