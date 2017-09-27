<?php

namespace Prisjakt\Unleash\Feature;

class Strategy
{
    private $name;
    private $parameters;

    public function __construct(string $name, array $parameters)
    {
        $this->name = $name;
        $this->parameters = $parameters;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
