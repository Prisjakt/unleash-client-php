<?php

namespace Prisjakt\Unleash\Strategy;

class GradualRolloutSessionIdStrategy extends GradualRolloutParameterStrategy
{

    public function __construct()
    {
        parent::__construct("sessionId");
    }

    public function getName(): string
    {
        return "gradualRolloutSessionId";
    }
}
