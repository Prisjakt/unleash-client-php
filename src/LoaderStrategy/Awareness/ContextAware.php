<?php

namespace Prisjakt\Unleash\LoaderStrategy\Awareness;

use Prisjakt\Unleash\Cache\CacheInterface;
use Prisjakt\Unleash\Settings;

interface ContextAware
{
    public function setSettings(Settings $settings);
    public function setCache(CacheInterface $cache);
}
