<?php

declare(strict_types=1);

namespace CAMOO\Utils;

use Camoo\Config\Config;
use Camoo\Config\Parser\Json;
use ConfigInterop\ConfigInterface;

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
        return static::$all?->get($sKey);
    }

    public static function check(string $sKey): bool
    {
        return static::$all?->has($sKey) ?? false;
    }

    public static function get(): mixed
    {
        return static::$all?->all() ?? [];
    }

    public static function write(string $sKey, mixed $xValue = []): void
    {
        if (null === static::$all) {
            static::$all = new Config('{}', new Json(), true);
        }
        static::$all->set($sKey, $xValue);
    }

    public static function toInterop(?string $prefix = null): ConfigInterface
    {
        $adapter = new ConfigureAdapter();

        return $prefix !== null && $prefix !== '' ? $adapter->withPrefix($prefix) : $adapter;
    }
}
