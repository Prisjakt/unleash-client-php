<?php

namespace Prisjakt\Unleash\Tests;

use GuzzleHttp\Psr7\Response;
use Http\Mock\Client;
use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Prisjakt\Unleash\Helpers\Json;
use Prisjakt\Unleash\Settings;
use Prisjakt\Unleash\Strategy\StrategyInterface;
use Prisjakt\Unleash\Unleash;
use Prophecy\Argument\Token\AnyValueToken;

class UnleashTest extends TestCase
{
    public function testClientRespectsInstantiationRegisterSetting()
    {
        $settings = new Settings("appName", "instanceId");
        $strategies = [];
        $httpClient = new Client();
        $filesystem = new Filesystem(new NullAdapter());

        new Unleash($settings, $strategies, $httpClient, $filesystem);

        // TODO: probably a bit too basic. Check request headers,content too.
        $this->assertEquals(0, count($httpClient->getRequests()));

        $settings->setRegisterOnInstantiation(true);
        new Unleash($settings, $strategies, $httpClient, $filesystem);
        $this->assertEquals(1, count($httpClient->getRequests()));
    }

    public function testClientRegisterIgnoresNetworkError()
    {
        $settings = new Settings("appName", "instanceId");
        $settings->setRegisterOnInstantiation(true);
        $strategies = [];
        $httpClient = new Client();
        $filesystem = new Filesystem(new NullAdapter());

        $httpClient->addException(new \Exception("Oh no.. Something went wrong! is the internet down?"));

        new Unleash($settings, $strategies, $httpClient, $filesystem);

        $this->assertEquals(1, count($httpClient->getRequests()));
    }

    public function testIsEnabledWorks()
    {
        $defaultStrategyImpl = $this->prophesize(StrategyInterface::class);
        $defaultStrategyImpl->getName()->willReturn("default");
        $defaultStrategyImpl->isEnabled(new AnyValueToken(), new AnyValueToken())->willReturn(true);

        $settings = new Settings("appName", "instanceId");
        $strategies = [$defaultStrategyImpl->reveal()];
        $httpClient = new Client();
        $filesystem = new Filesystem(new NullAdapter());
        $featuresData = $this->getFeaturesData();

        $httpClient->addResponse(new Response(200, [], Json::encode($featuresData)));

        $unleash = new Unleash($settings, $strategies, $httpClient, $filesystem);


        // Banana should always return false since the feature is disabled
        $this->assertFalse($unleash->isEnabled("banana", [], false));
        $this->assertFalse($unleash->isEnabled("banana", [], true));

        // mango should return false since it exists, is enabled but we haven't implemented its strategies.
        $this->assertFalse($unleash->isEnabled("mango", [], false));
        $this->assertFalse($unleash->isEnabled("mango", [], true));

        // pear should return default since we don't know anything about the feature at all. (it does not exist)
        $this->assertFalse($unleash->isEnabled("pear", [], false));
        $this->assertTrue($unleash->isEnabled("pear", [], true));

        // guava should return true since it's enabled and we have implemented its strategy (which always return true).
        $this->assertTrue($unleash->isEnabled("guava", [], false));
        $this->assertTrue($unleash->isEnabled("guava", [], true));
    }

    private function getFeaturesData()
    {
        return [
            "version" => 1,
            "features" => [
                [
                    "name" => "banana",
                    "description" => "description1",
                    "enabled" => false,
                    "strategies" => [["name" => "default", "parameters" => []]],
                    "createdAt" => time(),
                ],
                [
                    "name" => "mango",
                    "description" => "description2",
                    "enabled" => true,
                    "strategies" => [["name" => "super-rare-strategy", "parameters" => []]],
                    "createdAt" => time(),
                ],
                [
                    "name" => "guava",
                    "description" => "description3",
                    "enabled" => true,
                    "strategies" => [["name" => "default", "parameters" => []]],
                    "createdAt" => time(),
                ],

            ],
        ];
    }
}
