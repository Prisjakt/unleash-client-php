<?php

namespace Prisjakt\Unleash\Strategy;

class GradualRolloutRandomStrategy implements StrategyInterface
{

    public function getName(): string
    {
        return "gradualRolloutRandom";
    }

    public function isEnabled(array $parameters, array $context): bool
    {
        $percentage = $parameters["percentage"] ?? 0;

        $random = \random_int(1, 100);

        return $percentage >= $random;
    }
}
