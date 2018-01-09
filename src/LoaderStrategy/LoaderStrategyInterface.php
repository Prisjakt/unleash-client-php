<?php

namespace Prisjakt\Unleash\LoaderStrategy;

use Prisjakt\Unleash\Backend;
use Prisjakt\Unleash\DataStorage;

interface LoaderStrategyInterface
{
    /**
     * Called with a backend, implement strategy to fetch data
     * Can use supplied $backend for easy access to cache, file and server or you can roll your own
     * just as long as you return an instance of DataStorage or throw an exception.
     * @param Backend $backend
     * @return DataStorage
     */
    public function load(Backend $backend): DataStorage;
}
