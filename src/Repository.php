<?php

namespace Prisjakt\Unleash;

use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use Prisjakt\Unleash\Cache\CacheInterface;
use Prisjakt\Unleash\Exception\FileNotFoundException;
use Prisjakt\Unleash\Feature\Feature;
use Prisjakt\Unleash\Helpers\Json;
use Prisjakt\Unleash\Storage\StorageInterface;

class Repository
{
    const ENDPOINT_FEATURES = "/api/features";
    const ENDPOINT_REGISTER = "/api/client/register";

    private $settings;
    private $httpClient;
    private $storage;

    private $useCache;
    private $cache;
    private $updateLockKey;

    // TODO: move scalars to settings object?
    public function __construct(
        Settings $settings,
        StorageInterface $storage,
        HttpClient $httpClient = null,
        CacheInterface $cache = null
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
        } catch (FileNotFoundException $e) {
            // pass
        }


        if (is_null($cache)) {
            $this->useCache = false;
        } else {
            $this->useCache = true;
            $this->cache = $cache;
        }
        $this->updateLockKey = "PHP_UNLEASH_UPDATE_LOCK_{$this->settings->getAppName()}";
    }

    public function fetch($force = false)
    {
        if ($this->isStorageFresh() && !$force) {
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

        if (!$this->lockUpdates()) {
            return;
        }
        try {
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

            $response = $this->httpClient->sendRequest($request);
            $statusCode = $response->getStatusCode();
            $eTag = implode(", ", $response->getHeader("Etag"));


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
        } catch (\Exception $e) {
            $this->ignoreOrFail();
            return;
        } finally {
            $this->releaseUpdateLock();
        }
    }

    public function has(string $key): bool
    {
        return $this->storage->has($key);
    }

    public function get(string $key): Feature
    {
        return $this->storage->get($key);
    }

    public function getAll()
    {
        return $this->storage->getAll();
    }

    private function lockUpdates()
    {
        if (!$this->useCache) {
            return true;
        }

        return $this->cache->setExclusive($this->updateLockKey, 1);
    }

    private function releaseUpdateLock()
    {
        if (!$this->useCache) {
            return;
        }

        $this->cache->delete($this->updateLockKey);
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
