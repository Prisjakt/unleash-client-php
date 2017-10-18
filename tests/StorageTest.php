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

        $data = [
            [
                "name" => "feature",
                "description" => "description",
                "enabled" => true,
                "strategies" => [["name" => "default", "parameters" => []]],
                "createdAt" => time(),
            ],
        ];

        $storage = new Storage($this->appName, $filesystem);
        $storage->reset($data);

        $this->assertTrue($storage->has("feature"));
    }

    public function testBackupFileIsUpdatedIfAlreadyExists()
    {
        $filesystem = new Filesystem(new MemoryAdapter());

        $data = [
            [
                "name" => "feature",
                "description" => "description",
                "enabled" => true,
                "strategies" => [["name" => "default", "parameters" => []]],
                "createdAt" => time(),
            ],
        ];

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
        $data = [
            [
                "name" => "feature",
                "description" => "description",
                "enabled" => true,
                "strategies" => [["name" => "default", "parameters" => []]],
                "createdAt" => time(),
            ],
        ];

        $storage = new Storage($this->appName, new Filesystem(new NullAdapter()));
        $beforeReset = time();
        $storage->reset($data);
        $afterReset = time();
        $this->assertGreaterThanOrEqual($afterReset, $storage->getLastUpdated());
        $this->assertLessThanOrEqual($beforeReset, $storage->getLastUpdated());
    }


    public function testGetLastUpdatedFromCache()
    {
        $cachePool = new ArrayCachePool();
        $data = [
            [
                "name" => "feature",
                "description" => "description",
                "enabled" => true,
                "strategies" => [["name" => "default", "parameters" => []]],
                "createdAt" => time(),
            ],
        ];

        $storage = new Storage($this->appName, new Filesystem(new NullAdapter()), $cachePool);
        $beforeReset = time();
        $storage->reset($data);
        $afterReset = time();


        $backedUpStorage = new Storage($this->appName, null, $cachePool);
        $this->assertGreaterThanOrEqual($afterReset, $backedUpStorage->getLastUpdated());
        $this->assertLessThanOrEqual($beforeReset, $backedUpStorage->getLastUpdated());
    }
}
