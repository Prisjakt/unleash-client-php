<?php

namespace Prisjakt\Unleash\Tests\Strategy;

use Prisjakt\Unleash\Strategy\GradualRolloutUserIdStrategy;
use PHPUnit\Framework\TestCase;

// Thin on tests here since most of the logic is tested in GradualRolloutParameterStrategyTest
class GradualRolloutUserIdStrategyTest extends TestCase
{
    public function testStrategyReturnsCorrectName()
    {
        $strategy = new GradualRolloutUserIdStrategy();

        $this->assertEquals("gradualRolloutUserId", $strategy->getName());
    }

    public function testStrategyReturnsTrueForOneHundredPercent()
    {
        $strategy =new GradualRolloutUserIdStrategy();

        $parameters = [
            "percentage" => 100,
            "groupId" => "groupId",
        ];
        $context = [
            "userId" => "sorry, eh",
        ];

        $this->assertTrue($strategy->isEnabled($parameters, $context));
    }
}
