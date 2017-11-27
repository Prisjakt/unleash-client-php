<?php

namespace Prisjakt\Unleash\Tests\Strategy;

use PHPUnit\Framework\TestCase;
use Prisjakt\Unleash\Strategy\ApplicationHostnameStrategy;

class ApplicationHostnameStrategyTest extends TestCase
{
    public function testReturnsFalseIfParametersIsEmpty()
    {
        $strategy = new ApplicationHostnameStrategy();

        $this->assertFalse($strategy->isEnabled([], []));
    }

    public function testReturnsFalseIfHostnameDoesNotMatch()
    {
        $strategy = new ApplicationHostnameStrategy();

        $this->assertFalse($strategy->isEnabled(["hostNames" => "mango, guava"], []));
    }

    public function testReturnsTrueIfHostnameIsPresentInParameters()
    {
        $strategy = new ApplicationHostnameStrategy();

        $this->assertTrue($strategy->isEnabled(["hostNames" => gethostname()], []));
    }

    public function testHostnameFromContextWorks()
    {
        $strategy = new ApplicationHostnameStrategy();

        $this->assertTrue($strategy->isEnabled(["hostNames" => "banana,pineapple,mango"], ["HOSTNAME" => "mango"]));
        $this->assertFalse($strategy->isEnabled(["hostNames" => "banana,pineapple"], ["HOSTNAME" => "mango"]));
    }

    public function testStrategyReturnsCorrectName()
    {
        $strategy = new ApplicationHostnameStrategy();

        $this->assertEquals("applicationHostname", $strategy->getName());
    }
}
