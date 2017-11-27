<?php

namespace Prisjakt\Unleash\Strategy;

class GradualRolloutUserIdStrategy extends GradualRolloutParameterStrategy
{
    public function __construct()
    {
        parent::__construct("userId");
    }

    public function getName(): string
    {
        return "gradualRolloutUserId";
    }
}
