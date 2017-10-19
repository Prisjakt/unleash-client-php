<?php

namespace Prisjakt\Unleash;

class Settings
{
    const DEFAULT_UPDATE_INTERVAL_SECONDS = 15;

    private $appName;
    private $instanceId;
    private $unleashHost;
    private $updateInterval;

    public function __construct(
        string $appName,
        string $instanceId,
        string $unleashHost = "http://localhost:4242",
        int $updateInterval = self::DEFAULT_UPDATE_INTERVAL_SECONDS
    ) {

        $this->appName = $appName;
        $this->instanceId = $instanceId;
        $this->unleashHost = $this->stripTrailingSlashes($unleashHost);
        $this->updateInterval = $updateInterval;
    }

    public function getAppName(): string
    {
        return $this->appName;
    }

    public function getInstanceId(): string
    {
        return $this->instanceId;
    }

    public function getUnleashHost(): string
    {
        return $this->unleashHost;
    }

    public function getUpdateInterval(): int
    {
        return $this->updateInterval;
    }

    private function stripTrailingSlashes(string $url): string
    {
        return preg_replace("%/+$%", "", $url);
    }
}
