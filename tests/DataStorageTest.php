<?php

namespace Prisjakt\Unleash\Tests;

use PHPUnit\Framework\TestCase;
use Prisjakt\Unleash\DataStorage;
use Prisjakt\Unleash\Exception\NoSuchFeatureException;

class DataStorageTest extends TestCase
{
    public function testSetTimestamp()
    {
        $dataStorage = new DataStorage();

        $timestamp = time();
        $dataStorage->setTimestamp($timestamp);

        $this->assertEquals($timestamp, $dataStorage->getTimestamp());
    }

    public function testSetTimestampAuto()
    {
        $dataStorage = new DataStorage();

        $before = time();
        $dataStorage->setTimestamp();
        $after = time();

        $this->assertGreaterThanOrEqual($before, $dataStorage->getTimestamp());
        $this->assertLessThanOrEqual($after, $dataStorage->getTimestamp());
    }

    public function testETag()
    {
        $dataStorage = new DataStorage();

        $eTag = "abc123";
        $dataStorage->setEtag($eTag);

        $this->assertEquals($eTag, $dataStorage->getETag());
    }

    public function testFeatures()
    {
        $feature = [
            "name" => "test",
            "description" => "test",
            "enabled" => true,
            "strategies" => [
                [
                    "name" => "testStrat",
                    "parameters" => [
                        "items" => "a,b,c",
                    ],
                ],
            ],
            "createdAt" => time(),
        ];

        $dataStorage = new DataStorage();
        $dataStorage->setFeatures([$feature]);

        $this->assertTrue($dataStorage->has("test"));
        $this->assertEquals($dataStorage->get("test")->getName(), "test");
        $this->assertEquals(1, count($dataStorage->getFeatures()));

        $this->expectException(NoSuchFeatureException::class);
        $dataStorage->get("no-exist-will-throw");
    }
}
