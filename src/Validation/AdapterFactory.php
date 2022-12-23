<?php

declare(strict_types=1);

namespace CAMOO\Validation;

use CAMOO\Exception\Exception;
use CAMOO\Interfaces\ValidationInterface;

/**
 * Class AdapterFactory
 *
 * @author CamooSarl
 */
class AdapterFactory
{
    /** @var AdapterFactory $_created */
    private static $_created = null;

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from AdapterFactory::create() instead
     */
    private function __construct()
    {
    }

    /** prevent the instance from being cloned (which would create a second instance of it) */
    private function __clone()
    {
    }

    /** prevent from being unserialized (which would create a second instance of it) */
    private function __wakeup()
    {
    }

    /**
     * creates instances of Adapter Factory
     */
    public static function create(): AdapterFactory
    {
        static::$_created = new self();

        return static::$_created;
    }

    /**
     * @param string $adapter
     *
     * @throws Exception
     */
    public function get(?string $object = null, ?string $adapter = null): ValidationInterface
    {
        if (null === $adapter) {
            $adapter = ValidationInterface::DEFAULT_LIB;
        }

        $object = $object ?? 'Validator';
        $sAdapterClass = __NAMESPACE__ . '\\Adapters\\' . $adapter . '\\' . $object;
        if (!$this->classExists($sAdapterClass)) {
            throw new Exception(sprintf('Adapter Class %s cannot be foud', $sAdapterClass));
        }

        return new $sAdapterClass();
    }

    /**
     * @param string $name class name
     *
     * @return bool
     */
    protected function classExists($name)
    {
        return class_exists($name);
    }
}
