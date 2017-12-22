<?php

namespace Prisjakt\Unleash;

use Prisjakt\Unleash\Exception\NoSuchFeatureException;
use Prisjakt\Unleash\Feature\Feature;

class DataStorage
{
    private $timestamp = 0;
    private $features = [];
    private $eTag = "";

    public function getETag()
    {
        return $this->eTag;
    }

    public function setEtag(string $eTag)
    {
        $this->eTag = $eTag;
        return $this;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function setTimestamp(int $timestamp = null)
    {
        if (is_null($timestamp)) {
            $timestamp = time();
        }
        $this->timestamp = $timestamp;
        return $this;
    }

    public function getFeatures()
    {
        return $this->features;
    }

    public function setFeatures(array $features)
    {
        $this->features = [];
        foreach ($features as $feature) {
            $key = ($feature instanceof Feature) ? $feature->getName() : $feature["name"];
            $this->features[$key] = $feature;
        }
        return $this;
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

        if (!($this->features[$key] instanceof Feature)) {
            $this->features[$key] = Feature::fromArray($this->features[$key]);
        }

        return $this->features[$key];
    }
}
