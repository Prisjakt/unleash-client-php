<?php

namespace Prisjakt\Unleash\Tests\Strategy;

use Prisjakt\Unleash\Strategy\GradualRolloutSessionIdStrategy;
use PHPUnit\Framework\TestCase;

// Thin on tests here since most of the logic is tested in GradualRolloutParameterStrategyTest
class GradualRolloutSessionIdStrategyTest extends TestCase
{
    public function testStrategyReturnsCorrectName()
    {
        $strategy = new GradualRolloutSessionIdStrategy();

        $this->assertEquals("gradualRolloutSessionId", $strategy->getName());
    }

    public function testStrategyReturnsTrueForOneHundredPercent()
    {
        $strategy =new GradualRolloutSessionIdStrategy();

        $parameters = [
            "percentage" => 100,
            "groupId" => "groupId",
        ];
        $context = [
            "sessionId" => "sorry, eh",
        ];

        $this->assertTrue($strategy->isEnabled($parameters, $context));
    }
}
