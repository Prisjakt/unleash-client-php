<?php

namespace Prisjakt\Unleash\Tests\Strategy;

use lastguest\Murmur;
use PHPUnit\Framework\TestCase;
use Prisjakt\Unleash\Strategy\GradualRolloutParameterStrategy;

class GradualRolloutParameterStrategyTest extends TestCase
{
    public function testShouldBeEnabledIfPercentageIsSameAsNormalizedHash()
    {

        $parameter = "sessionId";
        $sessionId = "385h4389r2nr32423";
        $hashKey = "{$sessionId}:groupId";
        $parameters = [
            "percentage" => Murmur::hash3_int($hashKey) % 100 + 1,
            "groupId" => "groupId",
        ];

        $strategy = $this->getMockForAbstractClass(GradualRolloutParameterStrategy::class, [$parameter]);

        $this->assertTrue($strategy->isEnabled($parameters, [$parameter => $sessionId]));
    }

    public function testZeroPercentageMeansDisabled()
    {
        $parameter = "sessionId";
        $strategy = $this->getMockForAbstractClass(GradualRolloutParameterStrategy::class, [$parameter]);


        $parameters = [
            "percentage" => 0,
            "groupId" => "groupId",
        ];
        $context = [
            $parameter => "mango guava",
        ];

        $this->assertFalse($strategy->isEnabled($parameters, $context));
    }

    public function testOneHundredPercentMeansEnabled()
    {
        $parameter = "sessionId";
        $strategy = $this->getMockForAbstractClass(GradualRolloutParameterStrategy::class, [$parameter]);

        $parameters = [
            "percentage" => 100,
            "groupId" => "groupId",
        ];
        $context = [
            $parameter => "sorry, eh",
        ];

        $this->assertTrue($strategy->isEnabled($parameters, $context));
    }

    public function testParameterNotInContextMeansDisabled()
    {
        $strategy = $this->getMockForAbstractClass(GradualRolloutParameterStrategy::class, ["unknownParameter"]);

        $parameters = [
            "percentage" => 100,
            "groupId" => "groupId",
        ];
        $this->assertFalse($strategy->isEnabled($parameters, []));
    }
}
