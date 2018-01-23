<?php

namespace Prisjakt\Unleash\Tests\Metrics;

use GuzzleHttp\Psr7\Response;
use Http\Mock\Client;
use PHPUnit\Framework\TestCase;
use Prisjakt\Unleash\Exception\MetricsReportException;
use Prisjakt\Unleash\Helpers\Json;
use Prisjakt\Unleash\Metrics\Reporter;
use Prisjakt\Unleash\Metrics\Storage\Memcached;
use Prisjakt\Unleash\Settings;

class ReporterTest extends TestCase
{
    public function testReportWorks()
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(202));
        $settings = $this->getSettings();

        $cache = new \Prisjakt\Unleash\Cache\Memcached($this->getMemcachedInstance());

        $storage = new Memcached($settings->getAppName(), $settings->getInstanceId(), $cache);

        $reporter = new Reporter($httpClient, $settings);

        $storage->add("feature1", true);
        $storage->add("feature1", false);
        $storage->add("feature2", false);
        $storage->add("feature2", false);
        $storage->add("feature3", true);
        $storage->add("feature3", true);

        $storage->save();
        $reportData = [];

        foreach (["feature1", "feature2", "feature3"] as $feature) {
            $reportData[$feature] = $storage->get($feature);
        }

        $this->assertTrue($reporter->report(time(), $reportData));

        $requests = $httpClient->getRequests();

        $bodyData = Json::decode($requests[0]->getBody()->getContents(), true);
        $this->assertEquals(1, $bodyData["bucket"]["toggles"]["feature1"]["yes"]);
        $this->assertEquals(0, $bodyData["bucket"]["toggles"]["feature2"]["yes"]);
        $this->assertEquals(2, $bodyData["bucket"]["toggles"]["feature2"]["no"]);
        $this->assertEquals(2, $bodyData["bucket"]["toggles"]["feature3"]["yes"]);
    }

    public function testReportNoDataWorks()
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(202));
        $settings = $this->getSettings();

        $reporter = new Reporter($httpClient, $settings);

        $reportData = [];

        $this->assertTrue($reporter->report(time(), $reportData));

        $requests = $httpClient->getRequests();

        $this->assertEmpty($requests);
    }

    public function testErrorResponseThrows()
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(500));
        $settings = $this->getSettings();

        $cache = new \Prisjakt\Unleash\Cache\Memcached($this->getMemcachedInstance());

        $storage = new Memcached($settings->getAppName(), $settings->getInstanceId(), $cache);

        $reporter = new Reporter($httpClient, $settings);

        $storage->add("feature1", true);

        $reportData = [];

        $reportData["feature1"] = $storage->get("feature1");

        $this->expectException(MetricsReportException::class);
        $reporter->report(time(), $reportData);
    }

    private function getSettings(): Settings
    {
        $settings = new Settings(uniqid("appName_"), "instanceId");
        return $settings;
    }

    private function getMemcachedInstance()
    {
        if (!isset($_SERVER["PHPUNIT_MEMCACHED_HOST"])) {
            $this->markTestSkipped("Test skipped because no memcached env vars specified");
        }

        $host = $_SERVER["PHPUNIT_MEMCACHED_HOST"];
        $port = $_SERVER["PHPUNIT_MEMCACHED_PORT"];

        $memcached = new \Memcached();
        $result = $memcached->addServer($host, $port);
        if (!$result) {
            $this->markTestSkipped("Test skipped because could not add server: " . $memcached->getResultMessage());
        }

        return $memcached;
    }
}
