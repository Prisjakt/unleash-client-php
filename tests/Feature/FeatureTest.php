<?php

namespace Prisjakt\Unleash\Tests;

use PHPUnit\Framework\TestCase;
use Prisjakt\Unleash\Feature\Feature;
use Prisjakt\Unleash\Feature\Strategy;

class FeatureTest extends TestCase
{
    public function testExceptionIsThrownIfFeatureHasNoStrategies()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Feature("test", "test", false, [], time());
    }

    public function testCanCreateFeature()
    {
        $featureName = "test";
        $description = "This is a test";
        $feature = new Feature($featureName, $description, false, [$this->getDummyStrategy()], time());

        $this->assertEquals($featureName, $feature->getName());
        $this->assertEquals($description, $feature->getDescription());
    }

    public function testEnabledReturnsTrueForEnabledFeature()
    {
        $feature = new Feature("test", "test", true, [$this->getDummyStrategy()], time());
        $this->assertTrue($feature->isEnabled());
    }

    public function testEnabledReturnsFalseForDisabledFeature()
    {
        $feature = new Feature("test", "test", false, [$this->getDummyStrategy()], time());
        $this->assertFalse($feature->isEnabled());
    }

    public function testHydrateFeatureFromDataArrayFailsIfNotEverythingIsPassed()
    {
        $this->expectException(\Exception::class);
        $data = [
            "name" => "test",
        ];

        Feature::fromArray($data);
    }

    public function testHydrateFeatureFromDataArray()
    {
        $data = [
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

        $feature = Feature::fromArray($data);

        $this->assertEquals("test", $feature->getName());
        $this->assertTrue($feature->isEnabled());
        $this->assertInstanceOf(Strategy::class, $feature->getStrategies()[0]);
    }

    private function getDummyStrategy()
    {
        return [
            "name" => "DummyStrat",
            "parameters" => ["test" => "123 this does not matter abc"],
        ];
    }

    public function testFeatureStrategyThrowsOnMissingFields()
    {
        $data = [
            "name" => "test",
            "description" => "test",
            "enabled" => true,
            "strategies" => [
                [
                    "name" => "testStrat",
                ],
            ],
            "createdAt" => time(),
        ];

        $this->expectException(\InvalidArgumentException::class);
        Feature::fromArray($data);
    }
}
