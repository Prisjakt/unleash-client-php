<?php

namespace Prisjakt\Unleash\DataBackend;

use Prisjakt\Unleash\Cache\CacheInterface;
use Prisjakt\Unleash\DataStorage;

class Cache
{
    const KEY_PREFIX = "unleash-repo-v1-";
    const CACHE_TTL_WEEK = 604800;

    private $cache;
    private $cacheKey;
    private $ttl;

    public function __construct(CacheInterface $cache = null, $keyNamespace = "", $ttl = self::CACHE_TTL_WEEK)
    {
        $this->cache = $cache;
        $this->cacheKey = $this->getKey($keyNamespace);
        $this->ttl = $ttl;
    }

    public function load()
    {
        if ($this->cache == null) {
            return null;
        }

        $cacheItem = $this->cache->get($this->cacheKey);
        if (is_null($cacheItem)) {
            return null;
        }

        return \unserialize($cacheItem);
    }

    public function save(DataStorage $dataStorage): bool
    {
        if ($this->cache == null) {
            return false;
        }
        return $this->cache->set($this->cacheKey, \serialize($dataStorage), $this->ttl);
    }

    private function getKey($namespace = "")
    {
        return self::KEY_PREFIX . str_replace('[/\\]', '_', $namespace);
    }
}
