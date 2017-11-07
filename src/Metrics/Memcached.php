<?php

namespace Prisjakt\Unleash\Metrics;

use Prisjakt\Unleash\Cache\CacheInterface;

class Memcached implements MetricsInterface
{
    const TTL_WEEK_SECONDS = 604800;
    protected $cachePrefix = "unleash-metrics-v1";
    protected $features;
    private $cache;
    private $start;
    private $ttl;

    public function __construct(
        string $appName,
        string $instanceId,
        CacheInterface $cache,
        $ttl = self::TTL_WEEK_SECONDS
    ) {
        $this->cache = $cache;
        $this->cachePrefix .= "__{$appName}--{$instanceId}";
        $this->features = [];
        $this->start = \time();
        $this->ttl = $ttl;
    }

    public function __destruct()
    {
        if (!empty($this->features)) {
            $this->report();
        }
    }

    public function report()
    {
        $this->cache->setExclusive($this->getStartTimeCacheKey(), $this->start);

        foreach ($this->features as $feature => $values) {
            foreach ($values as $key => $result) {
                $cacheKey = $this->getFeatureCacheKey($feature, $key);
                $this->cache->increment($cacheKey, $result, $result, $this->ttl);
            }
        }

        $this->start = \time();
        $this->features = [];
    }

    public function add(string $feature, bool $result)
    {
        // we add to memory first to minimize the number of round trips to memcached
        if (!isset($this->features[$feature])) {
            $this->features[$feature] = ["yes" => 0, "no" => 0];
        }
        $key = $result ? "yes" : "no";
        $this->features[$feature][$key] += 1;
    }

    protected function getStartTimeCacheKey()
    {
        return $this->normalizeCacheKey("{$this->cachePrefix}-META--start");
    }

    protected function getFeatureCacheKey(string $feature, string $postfix)
    {
        return $this->normalizeCacheKey("{$this->cachePrefix}-FEAT--{$feature}--{$postfix}");
    }

    private function normalizeCacheKey(string $key)
    {
        return str_replace('[/\\]', '_', $key);
    }
}
