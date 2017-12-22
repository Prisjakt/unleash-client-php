<?php

namespace Prisjakt\Unleash\Tests\DataBackend;

use GuzzleHttp\Psr7\Response;
use Http\Mock\Client;
use PHPUnit\Framework\TestCase;
use Prisjakt\Unleash\DataBackend\Http;
use Prisjakt\Unleash\Helpers\Json;
use Prisjakt\Unleash\Settings;

class HttpTest extends TestCase
{
    public function testLoad()
    {
        $featuresData = $this->getFeaturesData();

        $httpClient = new Client();
        $httpClient->addResponse(new Response(200, [], Json::encode($featuresData)));

        $httpBackend = new Http($this->getSettings(), $httpClient);

        $dataStorage = $httpBackend->load();

        $this->assertTrue($dataStorage->has("feature1"));

        $requests = $httpClient->getRequests();
        $this->assertEquals(1, count($requests));
    }

    public function testLoadNoClient()
    {
        $httpBackend = new Http($this->getSettings());

        $this->assertNull($httpBackend->load());
    }

    public function testLoadWithETag()
    {
        $featuresData = $this->getFeaturesData();

        $httpClient = new Client();
        $eTag = "And that's all she wrote!";

        $httpClient->addResponse(new Response(
            200,
            ["ETag" => $eTag],
            Json::encode($featuresData)
        ));
        $httpClient->addResponse(new Response(
            304,
            ["ETag" => $eTag]
        ));

        $httpBackend = new Http($this->getSettings(), $httpClient);

        $dataStorage = $httpBackend->load();

        $this->assertTrue($dataStorage->has("feature1"));
        $this->assertNotEmpty($dataStorage->getETag());

        $requests = $httpClient->getRequests();
        $this->assertEquals(1, count($requests));

        $dataStorage2 = $httpBackend->load($dataStorage);

        $this->assertEquals($dataStorage->get("feature1")->getName(), $dataStorage2->get("feature1")->getName());
    }

    public function testLoadServerError()
    {
        $httpClient = new Client();
        $httpClient->addResponse(new Response(500));
        $httpBackend = new Http($this->getSettings(), $httpClient);

        $this->assertNull($httpBackend->load());
    }

    private function getFeaturesData(): array
    {
        return [
            "version" => 1,
            "features" => [
                [
                    "name" => "feature1",
                    "description" => "description1",
                    "enabled" => false,
                    "strategies" => [["name" => "default", "parameters" => []]],
                    "createdAt" => time(),
                ],
                [
                    "name" => "feature2",
                    "description" => "description2",
                    "enabled" => true,
                    "strategies" => [["name" => "default", "parameters" => []]],
                    "createdAt" => time(),
                ],
            ],
        ];
    }

    private function getSettings($dataMaxAge = Settings::DEFAULT_MAX_AGE_SECONDS)
    {
        return new Settings("Test", "Test:Id", "localhost", $dataMaxAge);
    }


}
