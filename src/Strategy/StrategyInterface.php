<?php

namespace Prisjakt\Unleash\Strategy;

interface StrategyInterface
{
    public function getName(): string;
    public function isEnabled(array $parameters, array $context): bool;
}
