<?php

namespace Prisjakt\Unleash\Storage;

use Prisjakt\Unleash\Feature\Feature;

interface StorageInterface
{
    public function has(string $key): bool;

    public function get(string $key): Feature;

    public function getAll();

    public function reset(array $features, string $eTag = null);

    public function load();

    public function save();

    public function getLastUpdated(): float;

    public function resetLastUpdated(float $lastUpdated = null);

    public function getETag(): string;

    // TODO: need a better name for isHit.
    public function isHit(): bool;
}
