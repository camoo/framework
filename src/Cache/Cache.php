<?php
declare(strict_types=1);

namespace CAMOO\Cache;

use CAMOO\Utils\Configure;
use CAMOO\Exception\Exception as AppException;

/**
 * Class Cache
 * @author CamooSarl
 */
final class Cache
{
    /**
     * @param string $key
     * @param string|int|array|mixed $value
     * @param string $config
     * @return bool
     */
    public static function write(string $key, $value, string $config) : ?bool
    {
        $hConfig = self::getConfig($config);
        $class = $hConfig['className'];
        if (array_key_exists('serialize', $hConfig) && $hConfig['serialize'] === true) {
            $value = serialize($value);
        }
        return (new $class)->set($key, $value, Configure::read('Cache.'.$config.'.duration'));
    }

    /**
     * @param string $key
     * @param string $config
     * @return null|string|int|array|mixed
     */
    public static function read(string $key, string $config)
    {
        $hConfig = self::getConfig($config);
        $class = $hConfig['className'];

        $value = (new $class)->get($key);
        if (!empty($value) && array_key_exists('serialize', $hConfig) && $hConfig['serialize'] === true) {
            $value = unserialize($value);
        }

        return null !== $value? $value : false;
    }

    /**
     * @param string $key
     * @param string $config
     * @return bool
     */
    public static function delete(string $key, string $config) : bool
    {
        $hConfig = self::getConfig($config);
        $class = $hConfig['className'];
        return (new $class)->delete($key);
    }

    /**
     * @param string $key
     * @param string $config
     * @return bool
     */
    public static function check(string $key, string $config) : bool
    {
        $hConfig = self::getConfig($config);
        $class = $hConfig['className'];
        return (new $class)->has($key);
    }

    /**
     * @param string $config
     * @return bool
     */
    public static function clear(string $config) : bool
    {
        $hConfig = self::getConfig($config);
        $class = $hConfig['className'];
        return (new $class)->clear($key);
    }

    /**
     * @param string $config
     * @return array
     */
    private static function getConfig(string $config) : array
    {
        $default = ['className' => 'CAMOO\Cache\Filesystem'];
        if (!Configure::check('Cache.' . $config)) {
            throw new AppException(sprintf('Cache Configuration %s is missing', $config));
        }
        $hConfig = Configure::read('Cache.' . $config);
        $hConfig +=$default;
        $class = $hConfig['className'];
        if (!class_exists($class)) {
            throw new AppException(sprintf('ClassName %s Not found !', $class));
        }
        return $hConfig;
    }
}
