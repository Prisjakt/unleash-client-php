<?php

namespace Prisjakt\Unleash\Metrics;

interface MetricsInterface
{
    public function add(string $feature, bool $result);

    public function report();
}
