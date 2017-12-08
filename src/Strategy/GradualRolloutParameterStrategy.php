<?php

namespace Prisjakt\Unleash\Strategy;

use lastguest\Murmur;

abstract class GradualRolloutParameterStrategy implements StrategyInterface
{
    private $targetParameter;

    public function __construct(string $targetParameter)
    {
        $this->targetParameter = $targetParameter;
    }

    public function isEnabled(array $parameters, array $context): bool
    {
        $targetValue = $context[$this->targetParameter] ?? "";

        if (!$targetValue) {
            return false;
        }

        $percentage = $parameters["percentage"] ?? 0;
        $groupId = $parameters["groupId"] ?? "";
        $hashKey = "{$targetValue}:{$groupId}";

        $normalizedId = Murmur::hash3_int($hashKey) % 100 + 1;

        return $percentage > 0 && $normalizedId <= $percentage;
    }

    abstract public function getName(): string;
}
