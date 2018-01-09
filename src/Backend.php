<?php

namespace Prisjakt\Unleash;

use Http\Client\HttpClient;
use League\Flysystem\FilesystemInterface;
use Prisjakt\Unleash\Cache\CacheInterface;
use Prisjakt\Unleash\DataBackend\Backup;
use Prisjakt\Unleash\DataBackend\Cache;
use Prisjakt\Unleash\DataBackend\Http;

class Backend
{
    private $cache = null;
    private $backup = null;
    private $http = null;

    public function setCache(CacheInterface $cache = null, $namespace = "", $ttl = Cache::CACHE_TTL_WEEK)
    {
        $this->cache = new Cache($cache, $namespace, $ttl);
        return $this;
    }

    public function setBackup(FilesystemInterface $filesystem = null, $suffix = "")
    {
        $this->backup = new Backup($filesystem, $suffix);
        return $this;
    }

    public function setHttp(Settings $settings = null, HttpClient $httpClient = null)
    {
        $this->http = new Http($settings, $httpClient);
        return $this;
    }

    public function cache()
    {
        if ($this->cache === null) {
            $this->cache = new Cache();
        }
        return $this->cache;
    }

    public function backup()
    {
        if ($this->backup === null) {
            $this->backup = new Backup();
        }
        return $this->backup;
    }

    public function http()
    {
        if ($this->http == null) {
            $this->http = new Http();
        }
        return $this->http;
    }
}
