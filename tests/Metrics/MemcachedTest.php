<?php

namespace Prisjakt\Unleash\Tests\Metrics;

use PHPUnit\Framework\TestCase;
use Prisjakt\Unleash\Metrics\Memcached;

class MemcachedTest extends TestCase
{
    private $ttl = 1;

    public function testWithTextProtocol()
    {
        $appName = uniqid("unleash_memcached_metrics_test");
        $memcache = $this->getMemcachedInstance();

        $metrics = new Memcached($appName, $appName, $memcache, $this->ttl);

        $feature = "lemon";
        $metrics->add($feature, true);
        $metrics->add($feature, true);
        $metrics->add($feature, false);

        $cacheKeyYes = "unleash-metrics-v1::{$appName}--{$appName}-FEAT--{$feature}--yes";
        $cacheKeyNo = "unleash-metrics-v1::{$appName}--{$appName}-FEAT--{$feature}--no";
        $this->assertFalse($memcache->get($cacheKeyYes));
        $this->assertFalse($memcache->get($cacheKeyNo));
        $metrics->report();
        $this->assertEquals(2, $memcache->get($cacheKeyYes));
        $this->assertEquals(1, $memcache->get($cacheKeyNo));

        $metrics->add($feature, true);
        $metrics->report();
        $this->assertEquals(3, $memcache->get($cacheKeyYes));
    }

    public function testWithBinaryProtocol()
    {
        $appName = uniqid("unleash_memcached_metrics_test");
        $memcache = $this->getMemcachedInstance();
        $memcache->setOption(\Memcached::OPT_BINARY_PROTOCOL, 1);

        $metrics = new Memcached($appName, $appName, $memcache, $this->ttl);

        $feature = "lemon";
        $metrics->add($feature, true);
        $metrics->add($feature, true);
        $metrics->add($feature, false);

        $cacheKeyYes = "unleash-metrics-v1::{$appName}--{$appName}-FEAT--{$feature}--yes";
        $cacheKeyNo = "unleash-metrics-v1::{$appName}--{$appName}-FEAT--{$feature}--no";
        $this->assertFalse($memcache->get($cacheKeyYes));
        $this->assertFalse($memcache->get($cacheKeyNo));
        $metrics->report();
        $this->assertEquals(2, $memcache->get($cacheKeyYes));
        $this->assertEquals(1, $memcache->get($cacheKeyNo));

        $metrics->add($feature, true);
        $metrics->report();
        $this->assertEquals(3, $memcache->get($cacheKeyYes));
    }

    public function testReportsOnDestruct()
    {
        $appName = uniqid("unleash_memcached_metrics_test");
        $memcache = $this->getMemcachedInstance();

        $metrics = new Memcached($appName, $appName, $memcache, $this->ttl);

        $feature = "lemon";
        $metrics->add($feature, true);

        $cacheKeyYes = "unleash-metrics-v1::{$appName}--{$appName}-FEAT--{$feature}--yes";
        $this->assertFalse($memcache->get($cacheKeyYes));

        unset($metrics);
        $this->assertEquals(1, $memcache->get($cacheKeyYes));
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
