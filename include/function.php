<?php

if (!function_exists('getEnv')) {
    function getEnv($sStr = null)
    {
        return getenv($sStr);
    }
}

if (!function_exists('array_key_first')) {
	/**
	 * @param array $arr
	 * @return null|int|string|array|object|mixed
	 */
    function array_key_first(array $arr)
    {
        foreach ($arr as $key => $unused) {
            return $key;
        }
        return null;
    }
}
