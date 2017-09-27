<?php

namespace Prisjakt\Unleash\Feature;

class Feature
{
    private static $requiredFields = [
        "name",
        "description",
        "enabled",
        "strategies",
        "createdAt",
    ];
    private $name;
    private $description;
    private $enabled;
    private $strategies;
    private $createdAt; // TODO: validate timestamp?

    public function __construct(string $name, string $description, bool $enabled, array $strategies, $createdAt)
    {
        if (empty($strategies)) {
            throw new \InvalidArgumentException("Feature has no strategies. This should not be possible.");
        }

        $this->name = $name;
        $this->description = $description;
        $this->enabled = $enabled;
        $this->strategies = $strategies;
        $this->createdAt = $createdAt;
    }

    public static function fromArray(array $data): self
    {
        $missingFields = array_diff(self::$requiredFields, array_keys($data));
        if (!empty($missingFields)) {
            throw new \InvalidArgumentException("Create Feature, Missing fields: " . implode(", ", $missingFields));
        }

        return new self(
            $data["name"],
            $data["description"],
            $data["enabled"],
            self::hydrateStrategies($data["strategies"]),
            $data["createdAt"]
        );
    }

    private static function hydrateStrategies(array $strategies): array
    {
        return array_map(function ($strategy) {
            $missingFields = array_diff(["name", "parameters"], array_keys($strategy));
            if (!empty($missingFields)) {
                throw new \InvalidArgumentException(
                    "Create FeatureStrategy, Missing fields: " .
                    implode(", ", $missingFields)
                );
            }
            return new Strategy($strategy["name"], $strategy["parameters"]);
        }, $strategies);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getStrategies(): array
    {
        return $this->strategies;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
