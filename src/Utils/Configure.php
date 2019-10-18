<?php

namespace CAMOO\Utils;

use CAMOO\Cache;
use \Noodlehaus\Config;

class Configure
{
    private static $_oahConfigs;

    public static function load($sPath, $bMerge = false)
    {
        if ($bMerge !== true) {
            static::$_oahConfigs = Config::load($sPath);
        } else {
            static::$_oahConfigs->merge(Config::load($sPath));
        }
    }

    public static function read($sKey)
    {
        return static::$_oahConfigs->get($sKey);
    }

    public static function check($sKey)
    {
        return static::$_oahConfigs->offsetExists($sKey);
    }

    public static function get()
    {
        return static::$_oahConfigs->all();
    }
}
