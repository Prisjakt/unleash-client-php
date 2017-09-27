<?php

namespace Prisjakt\Unleash;

interface StrategyInterface
{
    public function getName(): string;
    public function isEnabled(array $parameters, array $context): bool;
}
