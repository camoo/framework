<?php
declare(strict_types=1);

namespace CAMOO\Validation;

use CAMOO\Interfaces\ValidationInterface;
use CAMOO\Exception\Exception;

/**
 * Class AdapterFactory
 * @author CamooSarl
 */
class AdapterFactory
{
    /** @var AdapterFactory $_created */
    private static $_created = null;

    /**
     * creates instances of Adapter Factory
     * @return AdapterFactory
     */
    public static function create() : AdapterFactory
    {
        static::$_created = new self;
        return static::$_created;
    }

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from AdapterFactory::create() instead
     */
    private function __construct()
    {
    }

    /**
     * prevent the instance from being cloned (which would create a second instance of it)
     */
    private function __clone()
    {
    }

    /**
     * prevent from being unserialized (which would create a second instance of it)
     */
    private function __wakeup()
    {
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
     * @param string $adapter
     * @return ValidationInterface
     * @throws Exception
     */
    public function get(?string $object=null, ?string $adapter=null) : ValidationInterface
    {
        if (null === $adapter) {
            $adapter = ValidationInterface::DEFAULT_LIB;
        }

        $object = $object ?? 'Validator';
        $sAdapterClass = __NAMESPACE__ .'\\Adapters\\' . $adapter .'\\' . $object;
        if (!$this->classExists($sAdapterClass)) {
            throw new Exception(sprintf('Adapter Class %s cannot be foud', $sAdapterClass));
        }
        return new $sAdapterClass();
    }
}
