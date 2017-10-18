<?php

namespace Prisjakt\Unleash;

// Handles cache (if any)
// Handles file backup
// Holds features (passed in from ->reset or loaded from cache/backup
// config needed: cacheinterface (optional), filesystem (optional)
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use Prisjakt\Unleash\Exception\NoSuchFeatureException;
use Prisjakt\Unleash\Feature\Feature;
use Prisjakt\Unleash\Helpers\Json;
use Psr\Cache\CacheItemPoolInterface;

class Storage
{
    private $filesystem;
    private $cachePool;
    private $useCache;
    private $saveKey;

    private $features;
    private $lastUpdated;
    private $eTag;

    public function __construct(
        string $appName,
        FilesystemInterface $filesystem = null,
        CacheItemPoolInterface $cachePool = null
    ) {

        if (is_null($filesystem)) {
            $filesystem = new Filesystem(new Local(sys_get_temp_dir()));
        }
        $this->filesystem = $filesystem;

        if (is_null($cachePool)) {
            $this->useCache = false;
        } else {
            $this->useCache = true;
            $this->cachePool = $cachePool;
        }

        $this->saveKey = "unleash-repo-v1-" . str_replace('[/\\]', '_', $appName);

        $this->load();
    }

    public function has(string $key): bool
    {
        return isset($this->features[$key]);
    }

    public function get(string $key): Feature
    {
        if (!$this->has($key)) {
            throw new NoSuchFeatureException("Feature '{$key}' not found.");
        }
        return $this->features[$key];
    }

    public function reset(array $data, $eTag = null)
    {
        foreach ($data as $featureData) {
            // TODO: maybe we want to lazily instantiate features in the future?
            // TODO: (I could imaging a neat performance boost if the server has a lot of features.)
            $feature = Feature::fromArray($featureData);
            $this->features[$feature->getName()] = $feature;
        }

        $this->lastUpdated = time();
        $this->eTag = $eTag;

        $this->save();
    }

    public function getLastUpdated(): int
    {
        return $this->lastUpdated;
    }

    public function getETag(): string
    {
        return $this->eTag ?? "";
    }

    private function save(): self
    {
        $data = [
            "eTag" => $this->eTag,
            "lastUpdate" => $this->lastUpdated,
            "features" => \serialize($this->features),
        ];
        $this->saveToCache($data);
        $this->saveToBackup($data);

        return $this;
    }

    private function saveToCache(array $data)
    {
        if (!$this->useCache) {
            return;
        }

        $cacheItem = $this->cachePool->getItem($this->saveKey);
        $cacheItem->set($data);
        $this->cachePool->save($cacheItem);
    }

    private function saveToBackup(array $data)
    {
        $data = Json::encode($data);
        if ($this->filesystem->has($this->saveKey)) {
            $this->filesystem->update($this->saveKey, $data);
        } else {
            $this->filesystem->write($this->saveKey, $data);
        }
    }

    private function load()
    {
        if ($this->loadfromCache()) {
            return;
        }
        $this->loadFromBackup();
    }

    private function loadFromCache(): bool
    {
        if (!$this->useCache) {
            return false;
        }
        $cacheItem = $this->cachePool->getItem($this->saveKey);
        if (!$cacheItem->isHit()) {
            return false;
        }

        $data = $cacheItem->get();
        $this->setData($data);

        return true;
    }

    private function loadFromBackup(): bool
    {
        if (!$this->filesystem->has($this->saveKey)) {
            return false;
        }

        $data = Json::decode($this->filesystem->read($this->saveKey), true);
        $this->setData($data);

        return true;
    }

    private function setData(array $data)
    {
        $this->features = \unserialize($data["features"]);
        $this->lastUpdated = $data["lastUpdate"];
        $this->eTag = $data["eTag"];
    }
}
