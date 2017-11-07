<?php

namespace Prisjakt\Unleash\Tests\Cache;

use PHPUnit\Framework\TestCase;
use Prisjakt\Unleash\Cache\Memcached;
use Prisjakt\Unleash\Exception\CacheException;

class MemcachedTest extends TestCase
{
    public function testSet()
    {
        $memcached = $this->getMemcachedInstance();
        $cache = new Memcached($memcached);

        $value = "orange";
        $key = "testSet";
        $this->assertTrue($cache->set($key, $value, 10));
        $this->assertEquals($value, $cache->get($key));
        $this->assertTrue($cache->delete($key));
    }

    public function testSetExclusive()
    {
        $memcached = $this->getMemcachedInstance();
        $cache = new Memcached($memcached);

        $value = "orange";
        $key = "testSetExclusive";
        $this->assertTrue($cache->set($key, $value, 10));
        $this->assertFalse($cache->setExclusive($key, "mango", 10));
        $this->assertEquals($value, $cache->get($key));
        $this->assertTrue($cache->delete($key));
    }

    public function testGetDefault()
    {
        $memcached = $this->getMemcachedInstance();
        $cache = new Memcached($memcached);

        $value = "banana";
        $this->assertEquals($value, $cache->get("fruit", $value));
    }

    public function testDeleteExisting()
    {
        $memcached = $this->getMemcachedInstance();
        $cache = new Memcached($memcached);

        $key = "testDeleteExisting";
        $this->assertTrue($cache->set($key, "papaya", 10));
        $this->assertTrue($cache->delete($key));
    }

    public function testDeleteNonExisting()
    {
        $memcached = $this->getMemcachedInstance();
        $cache = new Memcached($memcached);

        $this->assertFalse($cache->delete("testDeleteNonExisting"));
    }

    public function testIncrement()
    {
        $memcached = $this->getMemcachedInstance();
        $cache = new Memcached($memcached);

        $key = "testIncrement";
        $cache->increment($key, 10, 3, 5);
        $this->assertEquals(3, $cache->get($key));

        $cache->increment($key, 1);
        $this->assertEquals(4, $cache->get($key));

        $cache->increment($key, 10);
        $this->assertEquals(14, $cache->get($key));
        $this->assertTrue($cache->delete($key));
    }

    public function testIncrementBinary()
    {
        $memcached = $this->getMemcachedInstance();
        $memcached->setOption(\Memcached::OPT_BINARY_PROTOCOL, 1);
        $cache = new Memcached($memcached);

        $key = "testIncrement";
        $cache->increment($key, 10, 3, 5);
        $this->assertEquals(3, $cache->get($key));

        $cache->increment($key, 1);
        $this->assertEquals(4, $cache->get($key));

        $cache->increment($key, 10);
        $this->assertEquals(14, $cache->get($key));
        $this->assertTrue($cache->delete($key));
    }

    public function testDecrement()
    {
        $memcached = $this->getMemcachedInstance();
        $cache = new Memcached($memcached);

        $key = "testDecrement";

        $cache->decrement($key, 10, 3, 5);
        $this->assertEquals(3, $cache->get($key));

        $cache->decrement($key, 1);
        $this->assertEquals(2, $cache->get($key));

        $this->assertTrue($cache->delete($key));
    }

    public function testDecrementBinary()
    {
        $memcached = $this->getMemcachedInstance();
        $memcached->setOption(\Memcached::OPT_BINARY_PROTOCOL, 1);
        $cache = new Memcached($memcached);

        $key = "testDecrement";

        $cache->decrement($key, 10, 3, 5);
        $this->assertEquals(3, $cache->get($key));

        $cache->decrement($key, 1);
        $this->assertEquals(2, $cache->get($key));

        $this->assertTrue($cache->delete($key));
    }

    public function testDecrementStopsAtZero()
    {
        $memcached = $this->getMemcachedInstance();
        $cache = new Memcached($memcached);

        $key = "testDecrementStopsAtZero";

        $cache->decrement($key, 1, 3, 5);
        $this->assertEquals(3, $cache->get($key));

        $cache->decrement($key);
        $this->assertEquals(2, $cache->get($key));

        $cache->decrement($key, 100);
        $this->assertEquals(0, $cache->get($key));
        $this->assertTrue($cache->delete($key));
    }

    public function testInvalidKey()
    {
        $memcached = $this->getMemcachedInstance();
        $cache = new Memcached($memcached);

        $this->expectException(CacheException::class);
        $cache->set("this is not a valid key", "value", 1);
    }

    private function getMemcachedInstance()
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

        return $memcached;
    }
}
