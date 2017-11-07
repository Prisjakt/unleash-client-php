<?php

namespace Prisjakt\Unleash\Storage;

use Prisjakt\Unleash\Cache\CacheInterface;
use Prisjakt\Unleash\Feature\Feature;

class CachedStorage implements StorageInterface
{
    private $cache;
    private $storage;
    private $saveKey;

    public function __construct(string $appName, CacheInterface $cache, StorageInterface $storage)
    {
        $this->cache = $cache;
        $this->storage = $storage;
        $this->saveKey = "unleash-repo-v1-" . str_replace('[/\\]', '_', $appName);
    }

    public function has(string $key): bool
    {
        return $this->storage->has($key);
    }

    public function get(string $key): Feature
    {
        return $this->storage->get($key);
    }

    public function reset(array $features, string $eTag = null)
    {
        $this->storage->reset($features, $eTag);
    }

    public function load()
    {
        $cacheItem = $this->cache->get($this->saveKey);
        if (is_null($cacheItem)) {
            $this->storage->load();
            return;
        }

        $this->storage->reset(\unserialize($cacheItem["features"]), $cacheItem["eTag"]);
        $this->storage->resetLastUpdated($cacheItem["lastUpdated"]);
    }

    public function save()
    {
        $this->storage->save();

        $this->cache->set($this->saveKey, [
            "lastUpdated" => $this->getLastUpdated(),
            "eTag" => $this->getETag(),
            "features" => \serialize($this->getAll()),
        ]);
    }

    public function getLastUpdated(): float
    {
        return $this->storage->getLastUpdated();
    }

    public function resetLastUpdated(float $lastUpdated = null)
    {
        $this->storage->resetLastUpdated($lastUpdated);
    }

    public function getETag(): string
    {
        return $this->storage->getETag();
    }

    public function isHit(): bool
    {
        return $this->storage->isHit();
    }

    public function getAll()
    {
        return $this->storage->getAll();
    }
}
