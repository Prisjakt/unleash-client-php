<?php

namespace Prisjakt\Unleash\Cache;

use Prisjakt\Unleash\Exception\CacheException;

class Memcached implements CacheInterface
{
    private $memcached;

    public function __construct(\Memcached $memcached)
    {
        $this->memcached = $memcached;
    }

    public function get(string $key, $default = null)
    {
        $this->validateKey($key);
        $result = $this->memcached->get($key);

        if ($this->memcached->getResultCode() == \Memcached::RES_NOTFOUND) {
            return $default;
        }

        if ($result === false) {
            return null;
        }

        return $result;
    }

    public function set(string $key, $value, int $expiration = null): bool
    {
        $this->validateKey($key);
        $expiration = $expiration ?? 0;
        return $this->memcached->set($key, $value, $expiration);
    }

    public function setExclusive(string $key, $value, int $expiration = null): bool
    {
        $this->validateKey($key);
        $expiration = $expiration ?? 0;
        return $this->memcached->add($key, $value, $expiration);
    }

    public function delete(string $key): bool
    {
        $this->validateKey($key);
        return $this->memcached->delete($key);
    }

    public function increment(string $key, int $offset = 1, int $initialValue = 0, $expiration = null)
    {
        $this->validateKey($key);
        $expiration = $expiration ?? 0;
        if ($this->memcached->getOption(\Memcached::OPT_BINARY_PROTOCOL)) {
            return $this->incrementBinary($key, $offset, $initialValue, $expiration);
        } else {
            return $this->incrementAscii($key, $offset, $initialValue, $expiration);
        }
    }

    public function decrement(string $key, int $offset = 1, int $initialValue = 0, $expiration = null)
    {
        $this->validateKey($key);
        $expiration = $expiration ?? 0;
        if ($this->memcached->getOption(\Memcached::OPT_BINARY_PROTOCOL)) {
            return $this->decrementBinary($key, $offset, $initialValue, $expiration);
        } else {
            return $this->decrementAscii($key, $offset, $initialValue, $expiration);
        }
    }

    private function incrementAscii(string $key, int $offset, int $initialValue = 0, $expiration = 0)
    {
        $newValue = $initialValue;
        if (!$this->memcached->add($key, $initialValue, $expiration)) {
            // Since we cant even specify default increment value without
            // exceptions being thrown we have to set ttl with a second request.
            $newValue = $this->memcached->increment($key, $offset);
            $this->memcached->touch($key, $expiration);
        }
        return $newValue;
    }

    private function incrementBinary(string $key, int $offset, int $initialValue = 0, $expiration = 0)
    {
        $this->memcached->increment($key, $offset, $initialValue, $expiration);
    }

    private function decrementAscii(string $key, int $offset, int $initialValue = 0, $expiration = 0)
    {
        $newValue = $initialValue;
        if (!$this->memcached->add($key, $initialValue, $expiration)) {
            // Since we cant even specify default increment value without
            // exceptions being thrown we have to set ttl with a second request.
            $newValue = $this->memcached->decrement($key, $offset);
            $this->memcached->touch($key, $expiration);
        }
        return $newValue;
    }

    private function decrementBinary(string $key, int $offset, int $initialValue = 0, $expiration = 0)
    {
        $this->memcached->decrement($key, $offset, $initialValue, $expiration);
    }

    private function validateKey(string $key)
    {
        if (preg_match("%[^a-zA-Z0-9_\.-]%", $key)) {
            throw new CacheException("Invalid key. Permitted characters: A-Z, a-z, 0-9, _, and .");
        }
    }
}
