<?php

namespace Prisjakt\Unleash\Metrics;

class Memcached implements MetricsInterface
{
    const TTL_WEEK_SECONDS = 604800;
    protected $cachePrefix = "unleash-metrics-v1";
    protected $features;
    private $memcached;
    private $start;
    private $ttl;

    public function __construct(
        string $appName,
        string $instanceId,
        \Memcached $memcached,
        $ttl = self::TTL_WEEK_SECONDS
    ) {
        $this->memcached = $memcached;
        $this->cachePrefix .= "::{$appName}--{$instanceId}";
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
        // Use add since if we don't have an existing start time we want to set it, otherwise let it be.
        $this->memcached->add($this->getStartTimeCacheKey(), $this->start);

        foreach ($this->features as $feature => $values) {
            foreach ($values as $key => $result) {
                $cacheKey = $this->getFeatureCacheKey($feature, $key);
                $this->increment($cacheKey, $result);
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

    private function increment(string $key, int $offset)
    {
        if ($this->memcached->getOption(\Memcached::OPT_BINARY_PROTOCOL)) {
            $this->incrementBinary($key, $offset);
        } else {
            $this->incrementAscii($key, $offset);
        }
    }

    private function incrementAscii(string $key, int $offset)
    {
        if (!$this->memcached->add($key, $offset, $this->ttl)) {
            // Since we cant even specify default increment value without
            // exceptions being thrown we have to set ttl with a second request.
            $this->memcached->increment($key, $offset);
            $this->memcached->touch($key, $this->ttl);
        }
    }

    private function incrementBinary(string $key, int $offset)
    {
        $this->memcached->increment($key, $offset, $offset);
    }
}
