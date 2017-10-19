<?php

namespace Prisjakt\Unleash\Tests\Strategy;

use PHPUnit\Framework\TestCase;
use Prisjakt\Unleash\Strategy\Repository;
use Prisjakt\Unleash\Strategy\StrategyInterface;

class RepositoryTest extends TestCase
{
    public function testConstructorTakesSingleStrategy()
    {
        $strategyName = "strategy";
        $strategy = $this->getMockedStrategy($strategyName);
        $strategyRepository = new Repository($strategy);

        $strategies = $strategyRepository->getAll();
        $this->assertEquals($strategy, $strategies[$strategyName]);
    }

    public function testConstructorTakesArrayOfStrategies()
    {
        $strategyName = "strategy";
        $strategy = $this->getMockedStrategy($strategyName);

        $strategyRepository = new Repository([$strategy]);

        $this->assertEquals(1, count($strategyRepository->getAll()));
    }

    public function testDuplicateStrategiesThrows()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Repository($this->getMockedStrategies(["dupe", "dupe"]));
    }

    public function testAddMultipleStrategies()
    {
        $strategyRepository = new Repository();
        $strategyRepository->register($this->getMockedStrategies(["one", "two"]));

        $this->assertEquals(2, count($strategyRepository->getAll()));
    }

    public function testCanFetchSingleStrategyFromRepository()
    {
        $strategyRepository = new Repository();
        $strategyRepository->register($this->getMockedStrategies(["one", "two", "three", "four"]));

        $strategy = $strategyRepository->get("two");
        $this->assertEquals("two", $strategy->getName());
    }

    public function testCanRegisterSingleStrategyWithoutArray()
    {
        $strategyRepository = new Repository();
        $strategyRepository->register($this->getMockedStrategy("one"));

        $strategy = $strategyRepository->get("one");
        $this->assertEquals("one", $strategy->getName());
    }

    public function testCanAskIfStrategyExists()
    {
        $strategyRepository = new Repository();
        $strategyRepository->register($this->getMockedStrategies(["one", "two", "three", "four"]));

        $this->assertTrue($strategyRepository->has("one"));
        $this->assertFalse($strategyRepository->has("five"));
    }

    public function testGetAllRegisteredStrategyNames()
    {
        $strategyNames = ["one", "two", "three", "four"];
        $strategyRepository = new Repository();
        $strategyRepository->register($this->getMockedStrategies($strategyNames));

        $this->assertEquals($strategyNames, $strategyRepository->getNames());
    }

    public function testGetUnknownStrategyThrowsException()
    {
        $this->expectException(\Exception::class);
        $strategyRepository = new Repository();
        $strategyRepository->get("nope");
    }

    private function getMockedStrategy($strategyName)
    {
        $strategy = $this->prophesize(StrategyInterface::class);
        $strategy->getName()->willReturn($strategyName);
        $strategy->isEnabled()->willReturn(false);

        return $strategy->reveal();
    }

    private function getMockedStrategies(array $strategyNames)
    {
        return array_map(function ($name) {
            return $this->getMockedStrategy($name);
        }, $strategyNames);
    }
}
