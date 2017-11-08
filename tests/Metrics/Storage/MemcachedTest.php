<?php

namespace Prisjakt\Unleash\Tests\Metrics\Storage;

use Prisjakt\Unleash\Metrics\Storage\Memcached;
use PHPUnit\Framework\TestCase;

class MemcachedTest extends TestCase
{
    private $instanceId = "mango:papaya";
    private $ttl = 3;

    public function testAddYesOnly()
    {
        $memcached = $this->getMemcachedInstance();
        $cache = new \Prisjakt\Unleash\Cache\Memcached($memcached);
        $metrics = new Memcached($this->getAppName(), $this->instanceId, $cache, $this->ttl);

        $metrics->add("feature1", true);

        $featureStats = $metrics->get("feature1");

        $this->assertEquals(1, $featureStats["yes"]);
        $this->assertEquals(0, $featureStats["no"]);
    }

    public function testAddNoOnly()
    {
        $memcached = $this->getMemcachedInstance();
        $cache = new \Prisjakt\Unleash\Cache\Memcached($memcached);
        $metrics = new Memcached($this->getAppName(), $this->instanceId, $cache, $this->ttl);

        $metrics->add("feature1", false);

        $featureStats = $metrics->get("feature1");

        $this->assertEquals(0, $featureStats["yes"]);
        $this->assertEquals(1, $featureStats["no"]);
    }

    public function testAddMultipleTimes()
    {
        $memcached = $this->getMemcachedInstance();
        $cache = new \Prisjakt\Unleash\Cache\Memcached($memcached);
        $metrics = new Memcached($this->getAppName(), $this->instanceId, $cache, $this->ttl);

        $metrics->add("feature1", false);
        $metrics->add("feature1", true);
        $metrics->add("feature1", true);
        $metrics->add("feature1", false);
        $metrics->add("feature1", false);

        $featureStats = $metrics->get("feature1");

        $this->assertEquals(2, $featureStats["yes"]);
        $this->assertEquals(3, $featureStats["no"]);
    }

    public function testAddMultipleFeatures()
    {
        $memcached = $this->getMemcachedInstance();
        $cache = new \Prisjakt\Unleash\Cache\Memcached($memcached);
        $metrics = new Memcached($this->getAppName(), $this->instanceId, $cache, $this->ttl);

        $metrics->add("feature1", false);
        $metrics->add("feature1", true);
        $metrics->add("feature2", true);
        $metrics->add("feature2", false);
        $metrics->add("feature2", false);

        $featureStats = $metrics->get("feature1");
        $this->assertEquals(1, $featureStats["yes"]);
        $this->assertEquals(1, $featureStats["no"]);

        $featureStats2 = $metrics->get("feature2");
        $this->assertEquals(1, $featureStats2["yes"]);
        $this->assertEquals(2, $featureStats2["no"]);
    }

    public function testGetOnEmpty()
    {
        $memcached = $this->getMemcachedInstance();
        $cache = new \Prisjakt\Unleash\Cache\Memcached($memcached);
        $metrics = new Memcached($this->getAppName(), $this->instanceId, $cache, $this->ttl);

        $featureStats = $metrics->get("feature1");

        $this->assertEquals(0, $featureStats["yes"]);
        $this->assertEquals(0, $featureStats["no"]);
    }

    public function testDeleteAfterGet()
    {
        $memcached = $this->getMemcachedInstance();
        $cache = new \Prisjakt\Unleash\Cache\Memcached($memcached);
        $metrics = new Memcached($this->getAppName(), $this->instanceId, $cache, $this->ttl);

        $metrics->add("feature1", true);
        $metrics->add("feature1", false);
        $metrics->add("feature1", false);

        $featureStats = $metrics->get("feature1", true);

        $this->assertEquals(1, $featureStats["yes"]);
        $this->assertEquals(2, $featureStats["no"]);

        $featureStats = $metrics->get("feature1", true);

        $this->assertEquals(0, $featureStats["yes"]);
        $this->assertEquals(0, $featureStats["no"]);
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

    private function getAppName()
    {
        return uniqid("unleash_test_app_");
    }
}
