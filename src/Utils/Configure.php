<?php
declare(strict_types=1);

namespace CAMOO\Utils;

use CAMOO\Cache;
use \Noodlehaus\Config;
use \Noodlehaus\Parser\Json;

class Configure
{
    /** @var array Config */
    private static $_oahConfigs = null;

    /**
     * @param string $sPath
     * @param bool $bMerge
     *
     * @return void
     */
    public static function load(string $sPath, bool $bMerge = false) : void
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

    /**
     * @param string $key
     *
     * @return mixed value
     */
    public static function read(string $sKey)
    {
        return static::$_oahConfigs->get($sKey);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public static function check(string $sKey) : bool
    {
        return static::$_oahConfigs->offsetExists($sKey);
    }

    /**
     * @return mixed value
     */
    public static function get()
    {
        return static::$_oahConfigs->all();
    }

    /**
     * @param string $sKey
     * @param array $xValue
     *
     * @return void
     */
    public function write($sKey, $xValue=[]) : void
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

    /**
     * @param array $hNewConf
     * @param string $sKey
     * @param mixed $xValue
     *
     * @return void
     */
    private static function addConf(&$hNewConf, $sKey, $xValue) : void
    {
        $asKeys = explode(".", $sKey);
        foreach ($asKeys as $key) {
            $hNewConf = &$hNewConf[$key];
        }
        $hNewConf = $xValue;
    }
}
