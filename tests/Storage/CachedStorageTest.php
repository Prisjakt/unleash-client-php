<?php

namespace Prisjakt\Unleash\Tests\Storage;

use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use PHPUnit\Framework\TestCase;
use Prisjakt\Unleash\Cache\Memcached;
use Prisjakt\Unleash\Feature\Feature;
use Prisjakt\Unleash\Storage\BackupStorage;
use Prisjakt\Unleash\Storage\CachedStorage;

class CachedStorageTest extends TestCase
{
    private $appName = "test";

    public function testCachedStorageProxiesBackupStorage()
    {
        $backupStorage = new BackupStorage($this->appName, new Filesystem(new NullAdapter()));
        $cachedStorage = new CachedStorage($this->appName, $this->getCache(), $backupStorage);

        $cachedStorage->reset($this->getFeaturesData(), "some random string acting as etag");

        $this->assertTrue($backupStorage->has("feature"));
        $this->assertTrue($cachedStorage->has("feature"));
        $before = $cachedStorage->getLastUpdated();
        $cachedStorage->resetLastUpdated();

        $this->assertInstanceOf(Feature::class, $cachedStorage->get("feature"));
        $this->assertEquals($backupStorage->get("feature"), $cachedStorage->get("feature"));
        $this->assertEquals($backupStorage->getLastUpdated(), $cachedStorage->getLastUpdated());
        $this->assertGreaterThan($before, $backupStorage->getLastUpdated());

        $this->assertEquals($backupStorage->isHit(), $cachedStorage->isHit());
        $this->assertEquals($backupStorage->getETag(), $cachedStorage->getETag());
        $this->assertEquals($backupStorage->getAll(), $cachedStorage->getAll());
    }

    public function testSaveCachedSavesBackupToo()
    {
        $filesystem = new Filesystem(new MemoryAdapter());
        $cache = $this->getCache();

        $backupStorage = new BackupStorage($this->appName, $filesystem);
        $cachedStorage = new CachedStorage($this->appName, $cache, $backupStorage);
        $cachedStorage->reset($this->getFeaturesData(), "some random string acting as etag");
        $cachedStorage->save();

        $backupStorage2 = new BackupStorage($this->appName, $filesystem);
        $backupStorage2->load();

        $this->assertTrue($backupStorage2->has("feature"));
    }

    public function testLoadFromBackupIfCacheIsEmpty()
    {
        $filesystem = new Filesystem(new MemoryAdapter());
        $cache = $this->getCache();

        $backupStorage = new BackupStorage($this->appName, $filesystem);
        $backupStorage->reset($this->getFeaturesData(), "some random string acting as etag");
        $backupStorage->save();

        $this->assertTrue($backupStorage->has("feature"));

        $cachedStorage = new CachedStorage($this->appName, $cache, $backupStorage);
        $cachedStorage->load();

        $this->assertTrue($cachedStorage->has("feature"));
        $this->assertInstanceOf(Feature::class, $cachedStorage->get("feature"));
    }

    public function testLoadFromCache()
    {
        $filesystem = new Filesystem(new NullAdapter());
        $cache = $this->getCache();

        $backupStorage = new BackupStorage($this->appName, $filesystem);
        $cachedStorage = new CachedStorage($this->appName, $cache, $backupStorage);
        $cachedStorage->reset($this->getFeaturesData());
        $cachedStorage->save();
        $this->assertTrue($cachedStorage->has("feature"));

        $cachedStorage2 = new CachedStorage($this->appName, $cache, $backupStorage);
        $cachedStorage2->load();

        $this->assertTrue($cachedStorage2->has("feature"));
        $this->assertInstanceOf(Feature::class, $cachedStorage2->get("feature"));
    }

    private function getFeaturesData(): array
    {
        $data = [
            [
                "name" => "feature",
                "description" => "description",
                "enabled" => true,
                "strategies" => [["name" => "default", "parameters" => []]],
                "createdAt" => time(),
            ],
        ];
        return $data;
    }

    private function getCache()
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
