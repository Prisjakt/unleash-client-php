<?php

namespace Prisjakt\Unleash\Tests\Feature;

use PHPUnit\Framework\TestCase;
use Prisjakt\Unleash\Feature\Feature;
use Prisjakt\Unleash\Feature\Processor;
use Prisjakt\Unleash\Strategy\Repository;
use Prisjakt\Unleash\Strategy\StrategyInterface;
use Prophecy\Argument\Token\AnyValueToken;

class ProcessorTest extends TestCase
{
    public function testUnsupportedStrategyReturnsFalse()
    {
        $strategyRepository = new Repository();

        $feature = Feature::fromArray([
            "name" => "testName",
            "description" => "testDescription",
            "enabled" => true,
            "createdAt" => time(),
            "strategies" => [
                [
                    "name" => "testStrategy",
                    "parameters" => []
                ],
            ],
        ]);

        $featureProcessor = new Processor($strategyRepository);

        $this->assertFalse($featureProcessor->process($feature, [], true));
        $this->assertFalse($featureProcessor->process($feature, [], false));
    }

    public function testDisabledFeatureShouldReturnFalse()
    {
        $strategyRepository = new Repository();

        $feature = Feature::fromArray([
            "name" => "testName",
            "description" => "testDescription",
            "enabled" => false,
            "createdAt" => time(),
            "strategies" => [
                [
                    "name" => "testStrategy",
                    "parameters" => []
                ],
            ],
        ]);

        $featureProcessor = new Processor($strategyRepository);

        $this->assertFalse($featureProcessor->process($feature, [], true));
    }

    public function testMultipleStrategiesReturnTrueIfAnyOfThemIsTrue()
    {
        $firstObjectProphecy = $this->prophesize(StrategyInterface::class);
        $firstObjectProphecy->getName()->willReturn("testStrategy");
        $firstObjectProphecy->isEnabled(new AnyValueToken(), new AnyValueToken())->willReturn(false);
        $secondObjectProphecy = $this->prophesize(StrategyInterface::class);
        $secondObjectProphecy->getName()->willReturn("testStrategy2");
        $secondObjectProphecy->isEnabled(new AnyValueToken(), new AnyValueToken())->willReturn(true);

        $strategyRepository = new Repository([$firstObjectProphecy->reveal(), $secondObjectProphecy->reveal()]);

        $feature = Feature::fromArray([
            "name" => "testName",
            "description" => "testDescription",
            "enabled" => true,
            "createdAt" => time(),
            "strategies" => [
                [
                    "name" => "testStrategy",
                    "parameters" => []
                ],
                [
                    "name" => "testStrategy2",
                    "parameters" => []
                ],
            ],
        ]);

        $featureProcessor = new Processor($strategyRepository);

        $this->assertTrue($featureProcessor->process($feature, [], false));
    }

    public function testMultipleStrategiesReturnAsSoonAsTrueIsFound()
    {
        $firstObjectProphecy = $this->prophesize(StrategyInterface::class);
        $firstObjectProphecy->getName()->willReturn("testStrategy");
        $firstObjectProphecy->isEnabled(new AnyValueToken(), new AnyValueToken())->willReturn(false);
        $firstObjectProphecy->isEnabled(new AnyValueToken(), new AnyValueToken())->shouldBeCalled();

        $secondObjectProphecy = $this->prophesize(StrategyInterface::class);
        $secondObjectProphecy->getName()->willReturn("testStrategy2");
        $secondObjectProphecy->isEnabled(new AnyValueToken(), new AnyValueToken())->willReturn(true);
        $secondObjectProphecy->isEnabled(new AnyValueToken(), new AnyValueToken())->shouldBeCalled();

        $thirdObjectProphecy = $this->prophesize(StrategyInterface::class);
        $thirdObjectProphecy->getName()->willReturn("testStrategy3");
        $thirdObjectProphecy->isEnabled(new AnyValueToken(), new AnyValueToken())->shouldNotBeCalled();

        $strategyRepository = new Repository([
            $firstObjectProphecy->reveal(),
            $secondObjectProphecy->reveal(),
            $thirdObjectProphecy->reveal(),
        ]);

        $feature = Feature::fromArray([
            "name" => "testName",
            "description" => "testDescription",
            "enabled" => true,
            "createdAt" => time(),
            "strategies" => [
                [
                    "name" => "testStrategy",
                    "parameters" => []
                ],
                [
                    "name" => "testStrategy2",
                    "parameters" => []
                ],
                [
                    "name" => "testStrategy3",
                    "parameters" => []
                ],
            ],
        ]);

        $featureProcessor = new Processor($strategyRepository);

        $this->assertTrue($featureProcessor->process($feature, [], false));
    }

    public function testMultipleStrategiesAllFalseShouldReturnFalse()
    {
        $firstObjectProphecy = $this->prophesize(StrategyInterface::class);
        $firstObjectProphecy->getName()->willReturn("testStrategy");
        $firstObjectProphecy->isEnabled(new AnyValueToken(), new AnyValueToken())->willReturn(false);
        $secondObjectProphecy = $this->prophesize(StrategyInterface::class);
        $secondObjectProphecy->getName()->willReturn("testStrategy2");
        $secondObjectProphecy->isEnabled(new AnyValueToken(), new AnyValueToken())->willReturn(false);

        $strategyRepository = new Repository([$firstObjectProphecy->reveal(), $secondObjectProphecy->reveal()]);

        $feature = Feature::fromArray([
            "name" => "testName",
            "description" => "testDescription",
            "enabled" => true,
            "createdAt" => time(),
            "strategies" => [
                [
                    "name" => "testStrategy",
                    "parameters" => []
                ],
                [
                    "name" => "testStrategy2",
                    "parameters" => []
                ],
            ],
        ]);

        $featureProcessor = new Processor($strategyRepository);

        $this->assertTrue($featureProcessor->process($feature, [], true));
    }
}
