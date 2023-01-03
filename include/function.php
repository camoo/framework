<?php

use CAMOO\Di\CamooDi;
use Ray\Di\Name;

if (!function_exists('getEnv')) {
    function getEnv($sStr = null)
    {
        return getenv($sStr);
    }
}

if (!function_exists('array_key_first')) {
    /**
     * @return int|string|null
     */
    function array_key_first(array $arr)
    {
        foreach ($arr as $key => $unused) {
            return $key;
        }

        return null;
    }
}

if (!function_exists('di')) {
    function di(string $class, string $name = Name::ANY): mixed
    {
        return CamooDi::get($class, $name);
    }
}
