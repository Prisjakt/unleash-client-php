<?php

namespace Prisjakt\Unleash\Metrics\Storage;

interface StorageInterface
{
    public function add(string $feature, bool $result);

    public function get(string $feature, bool $clear = false);
}
