<?php

namespace Prisjakt\Unleash\Helpers;

class Json
{
    public static function encode($data): string
    {
        $json = \json_encode($data);
        $code = \json_last_error();

        if ($code !== JSON_ERROR_NONE) {
            throw new \Exception("Could not encode data. " . \json_last_error_msg(), $code);
        }

        return $json;
    }

    public static function decode(string $json, bool $assoc = false)
    {
        $data = \json_decode($json, $assoc);
        $code = \json_last_error();

        if ($code !== JSON_ERROR_NONE) {
            throw new \Exception("Could not decode json. " . \json_last_error_msg(), $code);
        }

        return $data;
    }
}
