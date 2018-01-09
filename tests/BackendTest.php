<?php

namespace Prisjakt\Unleash\Tests;

use Http\Mock\Client;
use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Prisjakt\Unleash\Backend;
use Prisjakt\Unleash\Cache\CacheInterface;
use Prisjakt\Unleash\DataBackend\Backup;
use Prisjakt\Unleash\DataBackend\Cache;
use Prisjakt\Unleash\DataBackend\Http;
use Prisjakt\Unleash\Settings;

class BackendTest extends TestCase
{
    public function testCache()
    {
        $backend = new Backend();

        $cache = $this->prophesize(CacheInterface::class)->reveal();

        $backend->setCache($cache);

        $this->assertInstanceOf(Cache::class, $backend->cache());
    }

    public function testBackup()
    {
        $backend = new Backend();

        $filesystem = new Filesystem(new NullAdapter());

        $backend->setBackup($filesystem);

        $this->assertInstanceOf(Backup::class, $backend->backup());
    }

    public function testHttp()
    {
        $backend = new Backend();

        $client = new Client();

        $backend->setHttp(new Settings("mango", "papaya"), $client);

        $this->assertInstanceOf(Http::class, $backend->http());
    }
}
