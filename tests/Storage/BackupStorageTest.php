<?php

namespace Prisjakt\Unleash\Tests\Storage;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use PHPUnit\Framework\TestCase;
use Prisjakt\Unleash\Exception\NoSuchFeatureException;
use Prisjakt\Unleash\Feature\Feature;
use Prisjakt\Unleash\Storage\BackupStorage;

class BackupStorageTest extends TestCase
{
    private $appName = "test";

    public function testResetData()
    {
        $filesystem = new Filesystem(new NullAdapter());

        $data = $this->getFeaturesData();

        $storage = new BackupStorage($this->appName, $filesystem);
        $storage->reset($data);

        $this->assertTrue($storage->has("feature"));
    }

    public function testResetResets()
    {
        $filesystem = new Filesystem(new NullAdapter());

        $data = $this->getFeaturesData();

        $storage = new BackupStorage($this->appName, $filesystem);
        $storage->reset($data);

        $data[0]["name"] = "feature2";
        $storage->reset($data);

        $this->assertTrue($storage->has("feature2"));
        $this->assertFalse($storage->has("feature"));
    }

    public function testConsecutiveSavesUpdatesExistingBackupFile()
    {
        $filesystem = new Filesystem(new MemoryAdapter());
        $data = $this->getFeaturesData();

        $storage = new BackupStorage($this->appName, $filesystem);
        $storage->reset($data);

        $storage->save();
        $this->assertTrue($storage->has("feature"));
        $data[0]["name"] = "feature2";
        $storage->reset($data);
        $storage->save();
        $this->assertTrue($storage->has("feature2"));

        $storage->load();
        $this->assertTrue($storage->has("feature2"));
    }

    public function testSaveAndLoadWorks()
    {
        $filesystem = new Filesystem(new MemoryAdapter());

        $data = $this->getFeaturesData();

        $storage = new BackupStorage($this->appName, $filesystem);
        $storage->reset($data);

        $storage->save();
        $this->assertTrue($storage->has("feature"));

        $storage2 = new BackupStorage($this->appName, $filesystem);
        $storage2->load();

        $this->assertTrue($storage2->has("feature"));
    }

    public function testDifferentAppNamesDoesNotCollide()
    {
        $filesystem = new Filesystem(new MemoryAdapter());

        $data = $this->getFeaturesData();

        $storage = new BackupStorage($this->appName, $filesystem);
        $storage->reset($data);

        $storage->save();
        $this->assertTrue($storage->has("feature"));

        $storage2 = new BackupStorage("DifferentAppName", $filesystem);
        $storage2->save();
        $storage2->load();

        $this->assertFalse($storage2->has("feature"));
    }

    public function testReturnedFeatureIsValidInstance()
    {
        $data = $this->getFeaturesData();

        $storage = new BackupStorage($this->appName, new Filesystem(new NullAdapter()));
        $storage->reset($data);

        $this->assertTrue($storage->has("feature"));
        $this->assertInstanceOf(Feature::class, $storage->get("feature"));
    }

    public function testGetNonExistingFeatureThrows()
    {
        $data = $this->getFeaturesData();
        $storage = new BackupStorage($this->appName, new Filesystem(new NullAdapter()));
        $storage->reset($data);

        $this->expectException(NoSuchFeatureException::class);
        $storage->get("doesnotexist");
    }

    public function testQueryStorageBeforeLoad()
    {
        $storage = new BackupStorage($this->appName, new Filesystem(new NullAdapter()));

        $this->expectException(\Exception::class);
        $storage->has("whatever");
    }

    public function testDefaultFilesystemWorks()
    {
        $appName = uniqid("unleashtest");
        $filesystem = new Filesystem(new Local(sys_get_temp_dir()));
        $saveKey = "unleash-repo-v1-" . str_replace('[/\\]', '_', $appName);
        $storage = new BackupStorage($appName);

        $storage->reset($this->getFeaturesData());

        $storage->save();


        $this->assertTrue($filesystem->has($saveKey));

        // since we've created a real file on the filesystem, clean up!
        $filesystem->delete($saveKey);
    }

    public function testLoadWhenNoBackupExistsThrows()
    {
        $filesystem = new Filesystem(new MemoryAdapter());
        $storage = new BackupStorage($this->appName, $filesystem);

        $this->expectException(\Exception::class);
        $storage->load();
    }

    public function testResetLastUpdated()
    {
        $filesystem = new Filesystem(new NullAdapter());
        $storage = new BackupStorage($this->appName, $filesystem);

        $storage->reset($this->getFeaturesData());

        $before = $storage->getLastUpdated();

        usleep(10000); // 10ms

        $storage->resetLastUpdated();
        $after = $storage->getLastUpdated();

        $this->assertGreaterThan($before, $after);
    }

    public function testGetAll()
    {
        $filesystem = new Filesystem(new NullAdapter());

        $data = $this->getFeaturesData();

        $storage = new BackupStorage($this->appName, $filesystem);
        $storage->reset($data);

        $allBeforeGetCall = $storage->getAll();
        $this->assertTrue($storage->has("feature"));
        $feature = $storage->get("feature");
        $this->assertInstanceOf(Feature::class, $feature);
        $this->assertArrayHasKey("feature", $allBeforeGetCall);

        // check if lazy instantiation is working, the get call above
        // should have converted the raw array to an instance of Feature
        $allAfterGetCall = $storage->getAll();
        $this->assertInstanceOf(Feature::class, $allAfterGetCall["feature"]);
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
}
