<?php

namespace CAMOO\Utils;

use CAMOO\Cache;
use \Noodlehaus\Config;
use \Noodlehaus\Parser\Json;

class Configure
{
    private static $_oahConfigs = null;

    public static function load($sPath, $bMerge = false)
    {
        if (file_exists($sPath)) {
            $conf = Config::load($sPath);
            if ($bMerge === true && null !== static::$_oahConfigs) {
                static::$_oahConfigs->merge($conf);
            } else {
                static::$_oahConfigs = $conf;
            }
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

    public function write($sKey, $xValue=[])
    {
        $hNewConf =[];
        self::addConf($hNewConf, $sKey, $xValue);
        $conf = Config::load(json_encode($hNewConf), new Json, true);
        if (null !== static::$_oahConfigs) {
            static::$_oahConfigs->merge($conf);
        } else {
            static::$_oahConfigs = $conf;
        }
    }

    private static function addConf(&$hNewConf, $sKey, $xValue)
    {
        $asKeys = explode(".", $sKey);
        foreach ($asKeys as $key) {
            $hNewConf = &$hNewConf[$key];
        }
        $hNewConf = $xValue;
    }
}
