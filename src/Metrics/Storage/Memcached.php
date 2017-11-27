<?php

namespace Prisjakt\Unleash\Metrics\Storage;

use Prisjakt\Unleash\Cache\CacheInterface;

class Memcached implements StorageInterface
{
    const TTL_WEEK_SECONDS = 604800;
    protected $cachePrefix = "unleash-metrics-v1";

    private $cache;
    private $ttl;

    public function __construct(
        string $appName,
        string $instanceId,
        CacheInterface $cache,
        $ttl = self::TTL_WEEK_SECONDS
    ) {
        $this->ttl = $ttl;
        $this->cachePrefix .= "__{$appName}--{$instanceId}";
        $this->cache = $cache;
    }

    public function add(string $key, bool $result)
    {
        $cacheKey = $this->getCacheKey($key, $result);
        $this->cache->increment($cacheKey, 1, 1, $this->ttl);
    }

    public function get(string $feature, bool $clear = false)
    {
        $featureStats = [];
        foreach ([true, false] as $result) {
            $cacheKey = $this->getCacheKey($feature, $result);
            $featureStats[$this->getResultAsString($result)] = $this->cache->get($cacheKey, 0);

            if ($clear) {
                $this->cache->delete($cacheKey);
            }
        }
        return $featureStats;
    }

    private function getCacheKey(string $feature, bool $result)
    {
        $key = $this->getResultAsString($result);
        return $this->normalizeCacheKey("{$this->cachePrefix}-FEAT--{$feature}--{$key}");
    }

    private function getResultAsString(bool $result): string
    {
        return $result ? "yes" : "no";
    }

    private function normalizeCacheKey(string $key)
    {
        return str_replace('[/\\]', '_', $key);
    }
}
