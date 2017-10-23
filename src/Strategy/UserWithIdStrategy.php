<?php

namespace Prisjakt\Unleash\Strategy;

class UserWithIdStrategy implements StrategyInterface
{

    public function getName(): string
    {
        return "userWithId";
    }

    public function isEnabled(array $parameters, array $context): bool
    {
        if (!isset($context["userId"])) {
            return false;
        }

        $userIds = \preg_split("%\s*,\s*%", $parameters["userIds"] ?? "", -1, PREG_SPLIT_NO_EMPTY);
        if (empty($parameters["userIds"])) {
            return false;
        }

        return in_array($context["userId"], $userIds);
    }
}
