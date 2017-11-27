<?php

namespace Prisjakt\Unleash\Strategy;

class Repository
{
    private $strategies = [];

    public function __construct($strategies = [])
    {
        if (!is_array($strategies)) {
            $strategies = [$strategies];
        }

        $this->register($strategies);
    }

    public function register($strategies): Repository
    {
        if (!is_array($strategies)) {
            $strategies = [$strategies];
        }
        foreach ($strategies as $strategy) {
            $this->doRegister($strategy);
        }
        return $this;
    }

    public function get(string $key): StrategyInterface
    {
        if (!isset($this->strategies[$key])) {
            // TODO: should we throw or return an empty strategy?
            throw new \Exception("Strategy '{$key}' not implemented/registered.");
        }
        return $this->strategies[$key];
    }

    public function getAll(): array
    {
        return $this->strategies;
    }

    public function has(string $key): bool
    {
        return isset($this->strategies[$key]);
    }

    public function getNames(): array
    {
        return array_keys($this->strategies);
    }

    /**
     * @param StrategyInterface $strategy
     * @return Repository
     */
    private function doRegister(StrategyInterface $strategy): self
    {
        if (isset($this->strategies[$strategy->getName()])) {
            throw new \InvalidArgumentException(
                "A Strategy with the name '{$strategy->getName()}' has already been registered"
            );
        }
        $this->strategies[$strategy->getName()] = $strategy;
        return $this;
    }
}
