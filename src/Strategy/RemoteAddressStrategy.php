<?php

namespace Prisjakt\Unleash\Strategy;

class RemoteAddressStrategy implements StrategyInterface
{

    public function getName(): string
    {
        return "remoteAddress";
    }

    public function isEnabled(array $parameters, array $context): bool
    {
        if (!isset($context["remoteAddress"])) {
            return false;
        }

        $ips = $parameters["IPs"] ?? "";

        $ips = \preg_split("%\s*,\s*%", $ips, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($ips)) {
            return false;
        }

        return in_array($context["remoteAddress"], $ips);
    }
}
