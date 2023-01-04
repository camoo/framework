<?php

declare(strict_types=1);

namespace CAMOO\Utils;

use Noodlehaus\Config;
use Noodlehaus\Parser\Json;

class Configure
{
    private static mixed $all = null;

    public static function load(string $sPath, bool $bMerge = false): void
    {
        if (!file_exists($sPath)) {
            return;
        }
        $conf = Config::load($sPath);
        if ($bMerge === true && null !== static::$all) {
            static::$all->merge($conf);

            return;
        }
        static::$all = $conf;
    }

    /** @return mixed value */
    public static function read(string $sKey): mixed
    {
        return static::$all->get($sKey);
    }

    public static function check(string $sKey): bool
    {
        return static::$all->offsetExists($sKey);
    }

    public static function get(): mixed
    {
        return static::$all->all();
    }

    public static function write(string $sKey, mixed $xValue = []): void
    {
        $hNewConf = [];
        self::addConf($hNewConf, $sKey, $xValue);
        $conf = Config::load(json_encode($hNewConf), new Json(), true);
        if (null !== static::$all) {
            static::$all->merge($conf);
        } else {
            static::$all = $conf;
        }
    }

    private static function addConf(array &$hNewConf, string $sKey, mixed $xValue): void
    {
        $asKeys = explode('.', $sKey);
        foreach ($asKeys as $key) {
            $hNewConf = &$hNewConf[$key];
        }
        $hNewConf = $xValue;
    }
}
