<?php

namespace Prisjakt\Unleash;

class Settings
{
    const DEFAULT_MAX_AGE_SECONDS = 15;

    private $appName;
    private $instanceId;
    private $unleashHost;
    private $dataMaxAge;
    private $registerOnInstantiation;

    public function __construct(
        string $appName,
        string $instanceId,
        string $unleashHost = "http://localhost:4242",
        int $dataMaxAge = self::DEFAULT_MAX_AGE_SECONDS
    ) {

        $this->appName = $appName;
        $this->instanceId = $instanceId;
        $this->unleashHost = $this->stripTrailingSlashes($unleashHost);
        $this->dataMaxAge = $dataMaxAge;
        $this->registerOnInstantiation = false;
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

    public function getDataMaxAge(): int
    {
        return $this->dataMaxAge;
    }

    public function getRegisterOnInstantiation(): bool
    {
        return $this->registerOnInstantiation;
    }

    public function setRegisterOnInstantiation(bool $value)
    {
        $this->registerOnInstantiation = $value;
    }

    private function stripTrailingSlashes(string $url): string
    {
        return preg_replace("%/+$%", "", $url);
    }
}
