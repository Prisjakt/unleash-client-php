<?php

namespace Prisjakt\Unleash\Strategy;

class ApplicationHostnameStrategy implements StrategyInterface
{

    public function getName(): string
    {
        return "applicationHostname";
    }

    public function isEnabled(array $parameters, array $context): bool
    {
        if (!isset($parameters["hostNames"])) {
            return false;
        }
        $hostNames = \preg_split("%\s*,\s*%", $parameters["hostNames"], -1, PREG_SPLIT_NO_EMPTY);

        $realHostname = $context["HOSTNAME"] ?? \gethostname();

        foreach ($hostNames as $hostName) {
            if ($hostName == $realHostname) {
                return true;
            }
        }

        return false;
    }
}
