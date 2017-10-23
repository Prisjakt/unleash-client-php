<?php

namespace Prisjakt\Unleash\Tests\Strategy;

use PHPUnit\Framework\TestCase;
use Prisjakt\Unleash\Strategy\GradualRolloutRandomStrategy;

class GradualRolloutRandomStrategyTest extends TestCase
{

    public function testStrategyReturnsCorrectName()
    {
        $strategy = new GradualRolloutRandomStrategy();

        $this->assertEquals("gradualRolloutRandom", $strategy->getName());
    }

    public function testReturnsWithinMarginOfOnePercent()
    {
        // the idea here is that given enough calls we should even out at the specified percentage.
        // I would rather take random out of the game entirely though...
        $rounds = 10000;

        $percentage = 10;
        $enabledCount = 0;
        $strategy = new GradualRolloutRandomStrategy();


        $start = microtime(true);
        for ($i = 0; $i < $rounds; $i++) {
            if ($strategy->isEnabled(["percentage" => $percentage], [])) {
                $enabledCount++;
            }
        }
        $total = microtime(true) - $start;

        $percentageEnabled = $enabledCount / $rounds * 100;

        $this->assertGreaterThanOrEqual($percentage - 1, $percentageEnabled);
        $this->assertLessThanOrEqual($percentage + 1, $percentageEnabled);
    }
}
