<?php

namespace App;

class Helper
{
    public static function array_replace_keys(array $array, array $keyMappings) : array
    {
        $data = [];

        foreach ($array as $key => $value) {
            $key = $keyMappings[$key] ?? $key;
            $data[$key] = $value;
        }

        return $data;
    }
}
