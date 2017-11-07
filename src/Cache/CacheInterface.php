<?php

namespace Prisjakt\Unleash\Cache;

interface CacheInterface
{
    public function get(string $key, $default = null);

    public function set(string $key, $value, int $ttl = null);

    public function setExclusive(string $key, $value, int $ttl = null);

    public function delete(string $key);

    public function increment(string $key, int $offset = 1, int $initialValue = 0, $ttl = null);

    public function decrement(string $key, int $offset = 1, int $initialValue = 0, $ttl = null);
}
