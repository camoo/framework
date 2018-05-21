<?php

namespace CAMOO\Utils;

use CAMOO\Cache;
use \Noodlehaus\Config;

class Configure
{
    private static $oCache = null;

    public function __construct()
    {
        if (self::$oCache === null) {
            self::$oCache = new Cache\Filesystem();
        }
    }

    public static function load($sPath, $bUseCache = false)
    {
        $xConfig = Config::load($sPath);
        if ($bUseCache === true) {
            $oCache = new Cache\Filesystem();
            if ($oCache->has('configure')) {
                $oCache->delete('configure', $xConfig);
            }
            $oCache->set('configure', $xConfig);
        }
        return $xConfig;
    }

    public static function read($sKey)
    {
        self::$oCache = new Cache\Filesystem();
        if ($xConfig = (self::$oCache->get('configure'))) {
            return $xConfig->get($sKey);
        }
    }

    public static function check($sKey)
    {
        self::$oCache = new Cache\Filesystem();
        if ($xConfig = (self::$oCache->get('configure'))) {
            return null !== $xConfig->offsetExists($sKey);
        }
    }

    public static function get()
    {
        self::$oCache = new Cache\Filesystem();
        if ($xConfig = (self::$oCache->get('configure'))) {
            return $xConfig->all();
        }
    }
}
