<?php

if (!function_exists('getEnv')) {
    function getEnv($sStr = null)
    {
        return getenv($sStr);
    }
}
