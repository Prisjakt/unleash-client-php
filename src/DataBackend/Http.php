<?php

namespace Prisjakt\Unleash\DataBackend;

use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use Prisjakt\Unleash\DataStorage;
use Prisjakt\Unleash\Helpers\Json;
use Prisjakt\Unleash\Settings;

class Http
{
    const ENDPOINT_FEATURES = "/api/features";

    private $client;
    private $settings;

    public function __construct(Settings $settings = null, HttpClient $httpClient = null)
    {
        $this->client = $httpClient;
        $this->settings = $settings ?? new Settings("Unknown", "Unknown");
    }

    /**
     * @param DataStorage|null $old
     * @return null|DataStorage
     */
    public function load(DataStorage $old = null)
    {
        if ($this->client === null) {
            return null;
        }

        $request = new Request(
            "get",
            $this->settings->getUnleashHost() . self::ENDPOINT_FEATURES,
            [
                "Content-Type" => "Application/Json",
                "UNLEASH-APPNAME" => $this->settings->getAppName(),
                "UNLEASH-INSTANCEID" => $this->settings->getInstanceId(),
            ]
        );

        if (!is_null($old)) {
            $request = $request->withHeader("If-None-Match", $old->getETag());
        }

        $response = $this->client->sendRequest($request);
        $statusCode = $response->getStatusCode();
        $eTag = implode(", ", $response->getHeader("Etag"));

        if ($statusCode === 304) {
            $old->setTimestamp();
            return $old;
        }

        if ($statusCode < 200 || $statusCode > 299) {
            return null;
        }

        $json = $response->getBody()->getContents();
        $data = Json::decode($json, true);

        return (new DataStorage())
            ->setTimestamp()
            ->setEtag($eTag)
            ->setFeatures($data["features"]);
    }
}
