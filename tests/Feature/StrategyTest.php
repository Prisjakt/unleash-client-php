<?php

namespace Prisjakt\Unleash\Tests\Feature;

use PHPUnit\Framework\TestCase;
use Prisjakt\Unleash\Feature\Strategy;

class StrategyTest extends TestCase
{
    public function testFeatureStrategyHasName()
    {
        $featureStrategy = new Strategy("test", ["apa" => "banan"]);

        $this->assertEquals("test", $featureStrategy->getName());
    }

    public function testFeatureStrategyHasParameters()
    {
        $parameters = ["apa" => "banan"];
        $featureStrategy = new Strategy("test", $parameters);

        $this->assertEquals($parameters, $featureStrategy->getParameters());
    }
}
