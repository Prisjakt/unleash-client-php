<?php

namespace Prisjakt\Unleash\Storage;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use Prisjakt\Unleash\Exception\FileNotFoundException;
use Prisjakt\Unleash\Exception\NoSuchFeatureException;
use Prisjakt\Unleash\Feature\Feature;
use Prisjakt\Unleash\Helpers\Json;

class BackupStorage implements StorageInterface
{
    private $features;
    private $isHit;
    private $lastUpdated;
    private $eTag;
    private $filesystem;
    private $saveKey;

    public function __construct(string $appName, FilesystemInterface $filesystem = null)
    {
        if (is_null($filesystem)) {
            $filesystem = new Filesystem(new Local(sys_get_temp_dir()));
        }
        $this->filesystem = $filesystem;

        $this->saveKey = "unleash-repo-v1-" . str_replace('[/\\]', '_', $appName);
    }

    public function has(string $key): bool
    {
        if (!$this->isHit()) {
            throw new \Exception("Storage has not been loaded with data (use load() or reset()).");
        }
        return isset($this->features[$key]);
    }

    public function get(string $key): Feature
    {
        if (!$this->has($key)) {
            throw new NoSuchFeatureException("Feature '{$key}' not found.");
        }

        if (!($this->features[$key] instanceof Feature)) {
            $this->features[$key] = Feature::fromArray($this->features[$key]);
        }

        return $this->features[$key];
    }

    public function reset(array $features, string $eTag = null)
    {
        $this->features = [];
        foreach ($features as $feature) {
            $key = ($feature instanceof Feature) ? $feature->getName() : $feature["name"];
            $this->features[$key] = $feature;
        }
        $this->lastUpdated = microtime(true);
        $this->eTag = $eTag;
        $this->isHit = true;
    }

    public function getLastUpdated(): float
    {
        return $this->lastUpdated ?? 0;
    }

    public function resetLastUpdated(float $lastUpdated = null)
    {
        $this->lastUpdated = $lastUpdated ?? microtime(true);
    }

    public function getETag(): string
    {
        return $this->eTag ?? "";
    }

    public function isHit(): bool
    {
        return $this->isHit ?? false;
    }

    public function load()
    {
        if ($this->isHit) {
            return;
        }
        if (!$this->filesystem->has($this->saveKey)) {
            throw new FileNotFoundException("Could not load '{$this->saveKey}' from file system");
        }
        $data = Json::decode($this->filesystem->read($this->saveKey), true);

        $this->lastUpdated = $data["lastUpdated"];
        $this->eTag = $data["eTag"];
        $this->features = \unserialize($data["features"]);
        $this->isHit = true;
    }

    public function save()
    {
        $json = Json::encode([
            "lastUpdated" => $this->getLastUpdated(),
            "eTag" => $this->getETag(),
            "features" => \serialize($this->features),
        ]);

        if ($this->filesystem->has($this->saveKey)) {
            $this->filesystem->update($this->saveKey, $json);
        } else {
            $this->filesystem->write($this->saveKey, $json);
        }
    }

    public function getAll()
    {
        return $this->features;
    }
}
