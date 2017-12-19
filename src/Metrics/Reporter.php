<?php

namespace Prisjakt\Unleash\Metrics;

use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use Prisjakt\Unleash\Exception\MetricsReportException;
use Prisjakt\Unleash\Helpers\Json;
use Prisjakt\Unleash\Settings;

class Reporter
{
    const ENDPOINT_METRICS = "/api/client/metrics";

    private $httpClient;
    private $settings;

    public function __construct(HttpClient $httpClient, Settings $settings)
    {
        $this->httpClient = $httpClient;
        $this->settings = $settings;
    }

    public function report(int $startTime, array $featureStats)
    {
        if (empty($featureStats)) {
            return;
        }

        $request = new Request(
            "post",
            $this->settings->getUnleashHost() . self::ENDPOINT_METRICS,
            ["Content-Type" => "Application/Json"],
            Json::encode([
                "appName" => $this->settings->getAppName(),
                "instanceId" => $this->settings->getInstanceId(),
                "bucket" => [
                    "start" => date("c", $startTime),
                    "stop" => date("c"),
                    "toggles" => $featureStats,
                ],
            ])
        );

        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() !== 202) {
            throw new MetricsReportException($response->getBody()->getContents(), $response->getStatusCode());
        }

        return true;
    }
}
