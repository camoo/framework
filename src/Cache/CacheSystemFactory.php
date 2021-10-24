<?php

namespace CAMOO\Cache;

use CAMOO\Interfaces\CacheSystemFactoryInterface;
use CAMOO\Exception\Exception;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * Class FileSystemFactory
 * @author CamooSarl
 */
final class CacheSystemFactory implements CacheSystemFactoryInterface
{

    /** @var CacheSystemFactoryInterface|null $created */
    private static $created = null;

    /**
     * creates instances of Factory
     * @return CacheSystemFactoryInterface
     */
    public static function create() : CacheSystemFactoryInterface
    {
        if (null === self::$created) {
            self::$created = new self;
        }

        return self::$created;
    }

    /**
     * @param string $name class name
     * @return bool
     */
    protected function classExists(string $name): bool
    {
        return class_exists($name);
    }

    /**
     * @param array $options
     *
     * @return FilesystemAdapter
     */
    public function getFileSystemAdapter(array $options = []) : FilesystemAdapter
    {
        $default = [
            'namespace' => CacheSystemFactoryInterface::CACHE_DIRNAME,
            'ttl'       => CacheSystemFactoryInterface::CACHE_TTL,
            'dirname'   => CacheSystemFactoryInterface::CACHE_DIRNAME
        ];
        $options = array_merge($default, $options);
        if (!$this->classExists(FilesystemAdapter::class)) {
            throw new Exception(sprintf(
                'Adapter Class %s cannot be found',
                'Symfony\Component\Cache\Adapter\FilesystemAdapter'
            ));
        }
        return new FilesystemAdapter($options['namespace'], $options['ttl'], TMP.'cache'.DS.$options['dirname']);
    }
}
