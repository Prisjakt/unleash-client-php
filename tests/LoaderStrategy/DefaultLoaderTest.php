<?php

namespace Prisjakt\Unleash\Tests\LoaderStrategy;

use GuzzleHttp\Psr7\Response;
use Http\Mock\Client;
use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use PHPUnit\Framework\TestCase;
use Prisjakt\Unleash\Backend;
use Prisjakt\Unleash\Cache\Memcached;
use Prisjakt\Unleash\DataStorage;
use Prisjakt\Unleash\Exception\NoBackendAvailableException;
use Prisjakt\Unleash\Helpers\Json;
use Prisjakt\Unleash\LoaderStrategy\DefaultLoader;
use Prisjakt\Unleash\Settings;

class DefaultLoaderTest extends TestCase
{
    public function testUseFreshCache()
    {
        $backend = $this->getBackend(true);

        $dataStorage = new DataStorage();
        $dataStorage->setTimestamp();
        $dataStorage->setFeatures($this->getFeaturesData()["features"]);

        $backend->cache()->save($dataStorage);

        $defaultLoader = new DefaultLoader();

        $loadedStorage = $defaultLoader->load($backend);
        $this->assertTrue($loadedStorage->has("banana"));
        $this->assertEquals(count($dataStorage->getFeatures()), count($loadedStorage->getFeatures()));
    }

    public function testUseFetchFromServerIfCacheEmptyOrStale()
    {
        $backend = $this->getBackend(true, true, true);

        $dataStorage = new DataStorage();
        $dataStorage->setTimestamp();
        $dataStorage->setFeatures($this->getFeaturesData()["features"]);

        $defaultLoader = new DefaultLoader();
        $cache = new Memcached($this->getMemcachedInstance());
        $defaultLoader->setCache($cache);

        $loadedStorage = $defaultLoader->load($backend);
        $this->assertTrue($loadedStorage->has("banana"));
        $this->assertEquals(count($dataStorage->getFeatures()), count($loadedStorage->getFeatures()));


        // test that cache and backup now has saved the fetched data.
        $timestamp = $loadedStorage->getTimestamp();
        $backend->setHttp(null, null);

        $cachedStorage = $defaultLoader->load($backend);

        $this->assertEquals($timestamp, $cachedStorage->getTimestamp());

        $backend->setCache(null);
        $backupStorage = $defaultLoader->load($backend);

        $this->assertEquals($timestamp, $backupStorage->getTimestamp());
    }

    public function testUseStaleCacheIfUpdatesLocked()
    {
        $settings = $this->getSettings();
        $backend = $this->getBackend(true, false, true);

        $dataStorage = new DataStorage();
        $dataStorage->setTimestamp(time() - 1000);
        $dataStorage->setFeatures($this->getFeaturesData()["features"]);
        $backend->cache()->save($dataStorage);

        $defaultLoader = new DefaultLoader();
        $cache = new Memcached($this->getMemcachedInstance());
        $defaultLoader->setCache($cache);
        $defaultLoader->setSettings($settings);

        $key = "PHP_UNLEASH_UPDATE_LOCK_{$settings->getAppName()}";
        $cache->set($key, 1, 10);
        $loadedStorage = $defaultLoader->load($backend);
        $this->assertTrue($loadedStorage->has("banana"));
        $this->assertEquals(count($dataStorage->getFeatures()), count($loadedStorage->getFeatures()));
    }

    public function testThrowsIfNothingWorks()
    {
        $this->expectException(NoBackendAvailableException::class);
        (new DefaultLoader())->load($this->getBackend());
    }

    public function testUseBackupIfServerAndCacheDoesNotWork()
    {
        $backend = $this->getBackend(true, true);

        $dataStorage = new DataStorage();
        $dataStorage->setTimestamp(time() - 1000);
        $dataStorage->setFeatures($this->getFeaturesData()["features"]);
        $backend->backup()->save($dataStorage);

        $defaultLoader = new DefaultLoader();

        $loadedStorage = $defaultLoader->load($backend);
        $this->assertTrue($loadedStorage->has("banana"));
        $this->assertEquals(count($dataStorage->getFeatures()), count($loadedStorage->getFeatures()));
        // backup load should save to cache  so next time cached (stale) data is used instead.
        $backend->setBackup(null);
        $loadedStorage = $defaultLoader->load($backend);
        $this->assertEquals(count($dataStorage->getFeatures()), count($loadedStorage->getFeatures()));
    }

    private function getBackend($cache = false, $backup = false, $http = false, Settings $settings = null): Backend
    {
        $backend = new Backend();


        if ($cache) {
            $backend->setCache(new Memcached($this->getMemcachedInstance()), uniqid("unleash-test"), 10);
        }

        if ($backup) {
            $backend->setBackup(new Filesystem(new MemoryAdapter()));
        }

        if ($http) {
            if ($settings === null) {
                $settings = $this->getSettings();
            }

            $httpClient = new Client();
            $httpClient->addResponse(new Response(200, [], Json::encode($this->getFeaturesData())));
            $backend->setHttp($settings, $httpClient);
        }
        return $backend;
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

    private function getSettings()
    {
        return new Settings(uniqid("unleash-test"), uniqid("unleash-test"));
    }

    private function getFeaturesData()
    {
        return [
            "version" => 1,
            "features" => [
                [
                    "name" => "banana",
                    "description" => "description1",
                    "enabled" => false,
                    "strategies" => [["name" => "default", "parameters" => []]],
                    "createdAt" => time(),
                ],
                [
                    "name" => "mango",
                    "description" => "description2",
                    "enabled" => true,
                    "strategies" => [["name" => "super-rare-strategy", "parameters" => []]],
                    "createdAt" => time(),
                ],
                [
                    "name" => "guava",
                    "description" => "description3",
                    "enabled" => true,
                    "strategies" => [["name" => "default", "parameters" => []]],
                    "createdAt" => time(),
                ],

            ],
        ];
    }
}
