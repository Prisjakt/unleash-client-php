<?php

namespace Prisjakt\Unleash\Tests\DataBackend;

use PHPUnit\Framework\TestCase;
use Prisjakt\Unleash\Cache\Memcached;
use Prisjakt\Unleash\DataBackend\Cache;
use Prisjakt\Unleash\DataStorage;

class CacheTest extends TestCase
{
    public function testSaveAndLoad()
    {
        $cache = $this->getCache();

        $cacheStore = new Cache($cache, "testSaveAndLoad");

        $dataStorage = new DataStorage();

        $now = time();
        $eTag = "abc123";
        $dataStorage->setTimestamp($now);
        $dataStorage->setEtag($eTag);

        $cacheStore->save($dataStorage);

        $dataStorage2 = $cacheStore->load();

        $this->assertEquals($now, $dataStorage2->getTimestamp());
        $this->assertEquals($eTag, $dataStorage2->getETag());
    }

    public function testLoadCacheEmpty()
    {
        $cache = $this->getCache();

        $cacheStore = new Cache($cache, "testLoadCacheEmpty", 1);

        $this->assertNull($cacheStore->load());
    }

    public function testLoadNoCache()
    {
        $cacheStore = new Cache(null, "testLoadNoCache", 1);

        $this->assertNull($cacheStore->load());
    }

    public function testSaveNoCacheAvailable()
    {
        $cacheStore = new Cache(null, "testLoadNoCache", 1);

        $dataStorage = new DataStorage();

        $dataStorage->setTimestamp();
        $dataStorage->setEtag("abc123");

        $this->assertFalse($cacheStore->save($dataStorage));
    }


    private function getCache(): Memcached
    {
        if (!isset($_SERVER["PHPUNIT_MEMCACHED_HOST"])) {
            $this->markTestSkipped("Test skipped because no memcached env vars specified");
        }

        $host = $_SERVER["PHPUNIT_MEMCACHED_HOST"];
        $port = $_SERVER["PHPUNIT_MEMCACHED_PORT"];

        $memcached = new \Memcached();
        $result = $memcached->addServer($host, $port);
        if (!$result) {
            $this->markTestSkipped("Test skipped because could not add server: " . $memcached->getResultMessage());
        }

        return new Memcached($memcached);
    }
}
