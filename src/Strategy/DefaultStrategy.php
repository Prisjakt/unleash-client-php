<?php

namespace Prisjakt\Unleash\Strategy;

class DefaultStrategy implements StrategyInterface
{
    public function getName(): string
    {
        return "default";
    }

    public function isEnabled(array $parameters, array $context): bool
    {
        return true;
    }
}
