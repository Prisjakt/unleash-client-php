<?php

namespace Prisjakt\Unleash\LoaderStrategy;

use Prisjakt\Unleash\Backend;
use Prisjakt\Unleash\Cache\CacheInterface;
use Prisjakt\Unleash\DataStorage;
use Prisjakt\Unleash\Exception\NoBackendAvailableException;
use Prisjakt\Unleash\LoaderStrategy\Awareness\ContextAware;
use Prisjakt\Unleash\Settings;

class DefaultLoader implements LoaderStrategyInterface, ContextAware
{
    const TIME_15_SECONDS = 15;
    /** @var  CacheInterface */
    private $cache;
    /** @var  Settings */
    private $settings;
    private $ttl;
    private $cacheIdentifier;

    public function __construct($refreshInterval = self::TIME_15_SECONDS)
    {
        $this->ttl = $refreshInterval;
    }

    public function load(Backend $backend): DataStorage
    {
        $cacheData = $backend->cache()->load();
        // we have data and it is fresh.
        if ($cacheData !== null && $this->isFresh($cacheData->getTimestamp())) {
            return $cacheData;
        }

        $haveLock = $this->lockUpdates();
        // we either have (stale) data or could not get an update lock.
        if (!$haveLock && $cacheData !== null) {
            return $cacheData;
        }

        // We could get an update lock!
        if ($haveLock) {
            try {
                $httpData = $backend->http()->load($cacheData);
                if ($httpData === null) {
                    throw new \Exception("http data is null");
                }

                if ($httpData !== null) {
                    $backend->cache()->save($httpData);
                    $backend->backup()->save($httpData);
                    return $httpData;
                }
            } catch (\Exception $e) {
                // something went wrong with server fetch, use stale cache data if available.
                if ($cacheData !== null) {
                    return $cacheData;
                }
            } finally {
                $this->releaseUpdateLock();
            }
        }
        $backupData = $backend->backup()->load();

        if ($backupData === null) {
            throw new NoBackendAvailableException();
        }
        $backend->cache()->save($backupData);
        return $backupData;
    }

    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function setSettings(Settings $settings)
    {
        $this->settings = $settings;
    }

    private function isFresh(int $timestamp): bool
    {
        return ($timestamp + $this->ttl) > time();
    }

    private function lockUpdates(): bool
    {
        if ($this->cache == null) {
            return true;
        }
        return $this->cache->setExclusive($this->getLockKey(), 1, 10);
    }

    private function releaseUpdateLock()
    {
        if ($this->cache == null) {
            return;
        }
        $this->cache->delete($this->getLockKey());
    }

    private function getLockKey()
    {
        return "PHP_UNLEASH_UPDATE_LOCK_{$this->getCacheIdentifier()}";
    }

    private function getCacheIdentifier()
    {
        if (!$this->cacheIdentifier) {
            if ($this->settings) {
                $this->cacheIdentifier = $this->settings->getAppName();
            } else {
                $this->cacheIdentifier = uniqid();
            }
        }

        return $this->cacheIdentifier;
    }
}
