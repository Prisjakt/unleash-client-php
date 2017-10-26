<?php

namespace Prisjakt\Unleash\Tests;

use Cache\Adapter\PHPArray\ArrayCachePool;
use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use PHPUnit\Framework\TestCase;
use Prisjakt\Unleash\Exception\NoSuchFeatureException;
use Prisjakt\Unleash\Storage;

class StorageTest extends TestCase
{
    private $appName = "test";

    public function testResetData()
    {
        $filesystem = new Filesystem(new MemoryAdapter());

        $data = $this->getFeaturesData();

        $storage = new Storage($this->appName, $filesystem);
        $storage->reset($data);

        $this->assertTrue($storage->has("feature"));
    }

    public function testBackupFileIsUpdatedIfAlreadyExists()
    {
        $filesystem = new Filesystem(new MemoryAdapter());

        $data = $this->getFeaturesData();

        $storage = new Storage($this->appName, $filesystem);
        $storage->reset($data);

        $data[0]["name"] = "feature2";
        $storage->reset($data);

        $this->assertTrue($storage->has("feature2"));
    }

    public function testLoadDataFromCache()
    {
        $cachePool = new ArrayCachePool();
        $featureName = "feature";
        $data = [
            [
                "name" => $featureName,
                "description" => "description",
                "enabled" => true,
                "strategies" => [["name" => "default", "parameters" => []]],
                "createdAt" => time(),
            ],
        ];

        // begin with populating the cache.
        $storage = new Storage($this->appName, new Filesystem(new NullAdapter()), $cachePool);
        $storage->reset($data);
        $this->assertTrue($storage->has($featureName));

        // now the second instance should have the same data (from cache).
        $backedUpStorage = new Storage($this->appName, null, $cachePool);
        $this->assertTrue($backedUpStorage->has($featureName));
    }

    public function testLoadDataFromBackup()
    {
        $filesystem = new Filesystem(new MemoryAdapter());

        $featureName = "feature";
        $data = [
            [
                "name" => $featureName,
                "description" => "description",
                "enabled" => true,
                "strategies" => [["name" => "default", "parameters" => []]],
                "createdAt" => time(),
            ],
        ];

        // begin with populating the filesystem.
        $storage = new Storage($this->appName, $filesystem);
        $storage->reset($data);
        $this->assertTrue($storage->has($featureName));

        // now the second instance should have the same data (from backup file).
        $backedUpStorage = new Storage($this->appName, $filesystem);
        $this->assertTrue($backedUpStorage->has($featureName));
    }

    public function testGetFeature()
    {
        $featureName = "feature";
        $data = [
            [
                "name" => $featureName,
                "description" => "description",
                "enabled" => true,
                "strategies" => [["name" => "default", "parameters" => []]],
                "createdAt" => time(),
            ],
        ];

        $storage = new Storage($this->appName, new Filesystem(new NullAdapter()));
        $storage->reset($data);
        $this->assertTrue($storage->has($featureName));

        $this->assertEquals($featureName, $storage->get($featureName)->getName());
    }

    public function testGetNonExistingFeature()
    {
        $storage = new Storage($this->appName, new Filesystem(new NullAdapter()));
        $this->expectException(NoSuchFeatureException::class);
        $storage->get("Surely this cannot exist? No it can't.. And don't call med Shirley");
    }

    public function testGetLastUpdated()
    {
        $data = $this->getFeaturesData();

        $storage = new Storage($this->appName, new Filesystem(new NullAdapter()));
        $beforeReset = microtime(true);
        $storage->reset($data);
        $afterReset = microtime(true);

        $this->assertGreaterThanOrEqual($beforeReset, $storage->getLastUpdated());
        $this->assertLessThanOrEqual($afterReset, $storage->getLastUpdated());
    }

    public function testGetLastUpdatedFromCache()
    {
        $cachePool = new ArrayCachePool();
        $data = $this->getFeaturesData();

        $storage = new Storage($this->appName, new Filesystem(new NullAdapter()), $cachePool);
        $beforeReset = microtime(true);
        $storage->reset($data);
        $afterReset = microtime(true);


        $backedUpStorage = new Storage($this->appName, null, $cachePool);

        $this->assertGreaterThanOrEqual($beforeReset, $backedUpStorage->getLastUpdated());
        $this->assertLessThanOrEqual($afterReset, $backedUpStorage->getLastUpdated());
    }

    public function testCanSetETagWhenResetting()
    {
        $eTag = "this is a super random string";
        $data = $this->getFeaturesData();

        $storage = new Storage($this->appName, new Filesystem(new NullAdapter()));
        $storage->reset($data, $eTag);

        $this->assertEquals($eTag, $storage->getETag());
    }

    public function testResetUpdated()
    {
        $storage = new Storage($this->appName, new Filesystem(new NullAdapter()));
        $storage->resetLastUpdated();
        $this->assertNotEquals(0, $storage->getLastUpdated());
    }


    public function testSuccessfulLoadFlag()
    {
        $filesystem = new Filesystem(new MemoryAdapter());

        $data = $this->getFeaturesData();

        $storage = new Storage($this->appName, $filesystem);
        $this->assertFalse($storage->hasData());

        $storage->reset($data);

        $this->assertTrue($storage->hasData());
        $this->assertTrue($storage->has("feature"));

        $backedUpStorage = new Storage($this->appName, $filesystem);

        $this->assertTrue($backedUpStorage->hasData());
        $this->assertTrue($backedUpStorage->has("feature"));
    }

    /**
     * @return array
     */
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


}
