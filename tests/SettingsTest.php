<?php

namespace Prisjakt\Unleash\Tests;

use PHPUnit\Framework\TestCase;
use Prisjakt\Unleash\Settings;

class SettingsTest extends TestCase
{
    public function testCanSetAndGetRequiredFields()
    {
        $appName = "testName";
        $instanceId = "testInstance";
        $settings = new Settings($appName, $instanceId);

        $this->assertEquals($appName, $settings->getAppName());
        $this->assertEquals($instanceId, $settings->getInstanceId());
    }

    public function testDefaultFieldsAreSet()
    {
        $settings = new Settings("name", "instance");


        $this->assertEquals(Settings::DEFAULT_UPDATE_INTERVAL_SECONDS, $settings->getUpdateInterval());
        $this->assertEquals("http://localhost:4242", $settings->getUnleashHost());
    }

    public function testTrailingSlashIsStrippedFromUnleashHost()
    {
        $settings = new Settings("name", "instance", "http://my-awesome-unleash-server.com/");

        $this->assertEquals("http://my-awesome-unleash-server.com", $settings->getUnleashHost());
    }
}
