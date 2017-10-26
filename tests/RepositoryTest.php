<?php

namespace Prisjakt\Unleash\Tests;

use Cache\Adapter\PHPArray\ArrayCachePool;
use GuzzleHttp\Psr7\Response;
use Http\Mock\Client;
use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use PHPUnit\Framework\TestCase;
use Prisjakt\Unleash\Feature\Feature;
use Prisjakt\Unleash\Helpers\Json;
use Prisjakt\Unleash\Repository;
use Prisjakt\Unleash\Settings;
use Prisjakt\Unleash\Storage;

class RepositoryTest extends TestCase
{
    public function testTryingToUseServerFetchWithoutHttpClientThrows()
    {
        $storage = new Storage($this->getSettings()->getAppName(), new Filesystem(new NullAdapter()));

        $this->expectException(\InvalidArgumentException::class);
        new Repository($this->getSettings(), $storage);
    }

    public function testFetchDataFromServer()
    {
        $featuresData = $this->getFeaturesData();

        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], Json::encode($featuresData)));

        $storage = new Storage($this->getSettings()->getAppName(), new Filesystem(new NullAdapter()));

        $repository = new Repository($this->getSettings(), $storage, $httpClient);

        $repository->fetch();

        $this->assertTrue($repository->has("feature1"));
        $this->assertTrue($repository->has("feature2"));
    }

    public function testUseStorageDataIfFresh()
    {
        $featuresData = $this->getFeaturesData();

        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], Json::encode($featuresData)));

        $storage = new Storage($this->getSettings()->getAppName(), new Filesystem(new NullAdapter()));

        $repository = new Repository($this->getSettings(), $storage, $httpClient);

        $repository->fetch();
        // Second pass. We should use cache now instead of asking the server.
        $repository->fetch();
        $requests = $httpClient->getRequests();

        $this->assertEquals(1, count($requests));
    }

    public function testFetchFromServerIfStorageIsStale()
    {
        $featuresData = $this->getFeaturesData();

        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], Json::encode($featuresData)));
        $httpClient->addResponse(new Response(200, [], Json::encode($featuresData)));

        $storage = new Storage($this->getSettings()->getAppName(), new Filesystem(new NullAdapter()));

        $repository = new Repository($this->getSettings(0), $storage, $httpClient);

        $repository->fetch();
        // Second pass. Since maxAge is zero (0) the storage should immediately be considered stale.
        $repository->fetch();

        $requests = $httpClient->getRequests();
        $this->assertEquals(2, count($requests));
    }

    public function testUseStaleStorageIfServerDoesNotRespond()
    {
        $featuresData = $this->getFeaturesData();

        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], Json::encode($featuresData)));

        $memoryFilesystem = new Filesystem(new MemoryAdapter());

        $repositoryFromServer = new Repository(
            $this->getSettings(),
            new Storage($this->getSettings()->getAppName(), $memoryFilesystem),
            $httpClient
        );
        $repositoryFromServer->fetch();

        $repositoryFromStorage = new Repository(
            $this->getSettings(0),
            new Storage($this->getSettings()->getAppName(), $memoryFilesystem),
            $httpClient
        );
        $httpClient->addException(new \Exception("Server is down or something..."));
        $repositoryFromStorage->fetch();

        $requests = $httpClient->getRequests();
        // we should still have made a total of two requests but even if the last response fails
        $this->assertEquals(2, count($requests));
        // we should still have data from cache/backup in the new instance.
        $this->assertTrue($repositoryFromStorage->has("feature1"));
    }

    public function testUseStaleStorageIfServerRefreshIsDisabled()
    {
        $featuresData = $this->getFeaturesData();

        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], Json::encode($featuresData)));

        $memoryFilesystem = new Filesystem(new MemoryAdapter());

        $repositoryFromServer = new Repository(
            $this->getSettings(),
            new Storage($this->getSettings()->getAppName(), $memoryFilesystem),
            $httpClient
        );
        $repositoryFromServer->fetch();

        $settings2 = $this->getSettings(0);
        $settings2->setRefreshFromServerIfStale(false);
        $repositoryFromStorage = new Repository(
            $settings2,
            new Storage($this->getSettings()->getAppName(), $memoryFilesystem),
            $httpClient
        );
        $httpClient->addException(new \Exception("Server is down or something..."));
        $repositoryFromStorage->fetch();

        $requests = $httpClient->getRequests();
        // since the second instance does not use server we should only have one request.
        $this->assertEquals(1, count($requests));
        // thus we should use the stored data instead, even if it is stale.
        $this->assertTrue($repositoryFromStorage->has("feature1"));
    }


    public function testDisabledServerFetchAndNoCacheThrows()
    {
        $settings = $this->getSettings();
        $settings->setRefreshFromServerIfStale(false);
        $repositoryFromStorage = new Repository(
            $settings,
            new Storage($this->getSettings()->getAppName(), new Filesystem(new NullAdapter()))
        );

        $this->expectException(\Exception::class);
        $repositoryFromStorage->fetch();
    }

    public function testLockedUpdateUsesStaleStorage()
    {
        $featuresData = $this->getFeaturesData();

        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], Json::encode($featuresData)));

        $storage = new Storage($this->getSettings()->getAppName(), new Filesystem(new MemoryAdapter()));

        $repositoryFromServer = new Repository(
            $this->getSettings(),
            $storage,
            $httpClient
        );
        $repositoryFromServer->fetch();

        $lockKey = "UPDATE_LOCK_{$this->getSettings()->getAppName()}";
        $cachePool = new ArrayCachePool();
        $lockItem = $cachePool->getItem($lockKey);
        $lockItem->set(true);
        $cachePool->save($lockItem);

        $staleRepository = new Repository(
            $this->getSettings(0),
            $storage,
            $httpClient,
            $cachePool
        );
        $staleRepository->fetch();

        $requests = $httpClient->getRequests();
        // we should still have made a total of two requests but even if the last response fails
        $this->assertEquals(1, count($requests));
        // we should still have data from cache/backup in the new instance.
        $this->assertTrue($staleRepository->has("feature1"));
    }

    public function testValidETagResetsLastUpdated()
    {
        $featuresData = $this->getFeaturesData();

        $httpClient = new Client();
        $httpClient->addResponse(new Response(
            200,
            ["ETag" => "And that's all she wrote!"],
            Json::encode($featuresData)
        ));
        $httpClient->addResponse(new Response(304, ["ETag" => "And that's all she wrote!"]));

        $storage = new Storage($this->getSettings()->getAppName(), new Filesystem(new NullAdapter()));
        $repository = new Repository($this->getSettings(0), $storage, $httpClient);

        $repository->fetch();
        $repository->fetch();

        $this->assertTrue($repository->has("feature1"));
        $this->assertTrue($repository->has("feature2"));
    }

    public function testPassingACacheEnabledLockingUpdatesWhichWillNotDoAnythingWonky()
    {
        $featuresData = $this->getFeaturesData();
        $cachePool = new ArrayCachePool();
        $httpClient = new Client();

        $httpClient->addResponse(new Response(200, [], Json::encode($featuresData)));

        $storage = new Storage($this->getSettings()->getAppName(), new Filesystem(new NullAdapter()));

        $repository = new Repository(
            $this->getSettings(0),
            $storage,
            $httpClient,
            $cachePool
        );

        $repository->fetch();
        $this->assertTrue($repository->has("feature1"));
        $this->assertInstanceOf(Feature::class, $repository->get("feature1"));
    }

    public function testFailingToLoadFeaturesFromEverywhereThrows()
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(500));

        $storage = new Storage($this->getSettings()->getAppName(), new Filesystem(new NullAdapter()));

        $repository = new Repository(
            $this->getSettings(),
            $storage,
            $httpClient
        );

        $this->expectException(\Exception::class);
        $repository->fetch();
    }

    public function testWrongResponseCodeUsesStaleStorageData()
    {
        $featuresData = $this->getFeaturesData();

        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], Json::encode($featuresData)));
        $httpClient->addResponse(new Response(500, [], "Oh no! Something went wrong on the server"));
        $memoryFilesystem = new Filesystem(new MemoryAdapter());

        $repositoryFromServer = new Repository(
            $this->getSettings(),
            new Storage($this->getSettings()->getAppName(), $memoryFilesystem),
            $httpClient
        );
        $repositoryFromServer->fetch();

        $httpClient = new Client();
        $httpClient->addResponse(new Response(500));


        $staleRepository = new Repository(
            $this->getSettings(0),
            new Storage($this->getSettings()->getAppName(), $memoryFilesystem),
            $httpClient
        );

        $staleRepository->fetch();
        $this->assertTrue($staleRepository->has("feature1"));
    }

    private function getFeaturesData(): array
    {
        return [
            "version" => 1,
            "features" => [
                [
                    "name" => "feature1",
                    "description" => "description1",
                    "enabled" => false,
                    "strategies" => [["name" => "default", "parameters" => []]],
                    "createdAt" => time(),
                ],
                [
                    "name" => "feature2",
                    "description" => "description2",
                    "enabled" => true,
                    "strategies" => [["name" => "default", "parameters" => []]],
                    "createdAt" => time(),
                ],
            ],
        ];
    }

    private function getSettings($dataMaxAge = Settings::DEFAULT_MAX_AGE_SECONDS)
    {
        return new Settings("Test", "Test:Id", "localhost", $dataMaxAge);
    }
}
