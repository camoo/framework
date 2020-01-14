<?php

namespace CAMOO\Cache;

use CAMOO\Interfaces\CacheSystemFactoryInterface;
use CAMOO\Interfaces\FilesystemAdapterInterface;
use CAMOO\Exception\Exception;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * Class FileSystemFactory
 * @author CamooSarl
 */
final class CacheSystemFactory implements CacheSystemFactoryInterface
{

    /** @var CacheSystemFactoryInterface|null $_created */
    private static $_created = null;

    /**
     * creates instances of Factory
     * @return CacheSystemFactoryInterface
     */
    public static function create() : CacheSystemFactoryInterface
    {
        if (null === static::$_created) {
            static::$_created = new self;
        }

        return static::$_created;
    }

    /**
     * @param string $name class name
     * @return bool
     */
    protected function classExists($name)
    {
        return class_exists($name);
    }

    /**
     * @param array $options
     *
     * @return FilesystemAdapterInterface
     * @throws Exception
     */
    public function getFileSystemAdapter(array $options=[]) : FilesystemAdapter
    {
        $default = [
            'namespace' => CacheSystemFactoryInterface::CACHE_DIRNAME,
            'ttl'		=> CacheSystemFactoryInterface::CACHE_TTL,
            'dirname'   => CacheSystemFactoryInterface::CACHE_DIRNAME
        ];
        $options = array_merge($default, $options);
        if (!$this->classExists(\Symfony\Component\Cache\Adapter\FilesystemAdapter::class)) {
            throw new Exception(sprintf('Adapter Class %s cannot be foud', 'Symfony\Component\Cache\Adapter\FilesystemAdapter'));
        }
        return new FilesystemAdapter($options['namespace'], $options['ttl'], TMP.'cache'.DS.$options['dirname']);
    }
}
