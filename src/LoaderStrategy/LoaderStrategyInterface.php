<?php

namespace Prisjakt\Unleash\LoaderStrategy;

use Prisjakt\Unleash\Backend;

interface LoaderStrategyInterface
{
    public function load(Backend $backend);
}
