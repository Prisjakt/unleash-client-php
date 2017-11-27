<?php

namespace Prisjakt\Unleash\Tests\Strategy;

use PHPUnit\Framework\TestCase;
use Prisjakt\Unleash\Strategy\RemoteAddressStrategy;

class RemoteAddressStrategyTest extends TestCase
{

    public function testStrategyHasCorrectName()
    {
        $strategy = new RemoteAddressStrategy();

        $this->assertEquals("remoteAddress", $strategy->getName());
    }

    public function testReturnsFalseIfIpNotInParameters()
    {
        $strategy = new RemoteAddressStrategy();

        $this->assertFalse($strategy->isEnabled(["IPs" => "127.0.0.1"], ["remoteAddress" => "localhost"]));
    }

    public function testReturnsFalseIfIpNotPassedInContext()
    {
        $strategy = new RemoteAddressStrategy();

        $this->assertFalse($strategy->isEnabled(["IPs" => "127.0.0.1"], []));
    }

    public function testReturnsFalseIfNoIpsInParameters()
    {
        $strategy = new RemoteAddressStrategy();

        $this->assertFalse($strategy->isEnabled([], ["remoteAddress" => "localhost"]));
    }

    public function testReturnsFalseIfNoIpInContext()
    {
        $strategy = new RemoteAddressStrategy();

        $this->assertFalse($strategy->isEnabled(["IPs" => "localhost"], []));
    }

    public function testReturnsTrueIfIpIsInParameters()
    {
        $strategy = new RemoteAddressStrategy();

        $context = ["remoteAddress" => "localhost"];

        $this->assertTrue($strategy->isEnabled(["IPs" => "127.0.0.1, localhost"], $context));
        $this->assertTrue($strategy->isEnabled(["IPs" => "127.0.0.1,localhost"], $context));
        $this->assertTrue($strategy->isEnabled(["IPs" => "localhost"], $context));
        $this->assertTrue($strategy->isEnabled(["IPs" => "localhost,127.0.0.1"], $context));
        $this->assertTrue($strategy->isEnabled(["IPs" => "localhost, 127.0.0.1"], $context));
        $this->assertTrue($strategy->isEnabled(["IPs" => "example.com, localhost, 127.0.0.1"], $context));
        $this->assertTrue($strategy->isEnabled(["IPs" => "example.com, localhost,127.0.0.1"], $context));
    }
}
