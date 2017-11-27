<?php

namespace Prisjakt\Unleash\Tests\Strategy;

use Prisjakt\Unleash\Strategy\DefaultStrategy;
use PHPUnit\Framework\TestCase;

class DefaultStrategyTest extends TestCase
{
    public function testDefaultStrategyAlwaysReturnsTrue()
    {
        $strategy = new DefaultStrategy();

        $this->assertTrue($strategy->isEnabled([], []));
        $this->assertTrue($strategy->isEnabled(["one"], ["two"]));
        $this->assertTrue($strategy->isEnabled([1], [2]));
        $this->assertTrue($strategy->isEnabled(["one"], [1]));
        $this->assertTrue($strategy->isEnabled(["one"], [2]));
        $this->assertTrue($strategy->isEnabled(["rock"], ["rock"]));
    }

    public function testStrategyReturnsCorrectName()
    {
        $strategy = new DefaultStrategy();

        $this->assertEquals("default", $strategy->getName());
    }
}
