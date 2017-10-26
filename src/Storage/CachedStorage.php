<?php

namespace Prisjakt\Unleash\Storage;

use Prisjakt\Unleash\Feature\Feature;
use Psr\Cache\CacheItemPoolInterface;

class CachedStorage implements StorageInterface
{
    private $cachePool;
    private $storage;
    private $saveKey;

    public function __construct(string $appName, CacheItemPoolInterface $cachePool, StorageInterface $storage)
    {
        $this->cachePool = $cachePool;
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
        $cacheItem = $this->cachePool->getItem($this->saveKey);
        if (!$cacheItem->isHit()) {
            $this->storage->load();
            return;
        }

        $data = $cacheItem->get();
        $this->storage->reset(\unserialize($data["features"]), $data["eTag"]);
        $this->storage->resetLastUpdated($data["lastUpdated"]);
    }

    public function save()
    {
        $this->storage->save();

        $cacheItem = $this->cachePool->getItem($this->saveKey);
        $cacheItem->set([
            "lastUpdated" => $this->getLastUpdated(),
            "eTag" => $this->getETag(),
            "features" => \serialize($this->getAll()),
        ]);
        $this->cachePool->save($cacheItem);
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
