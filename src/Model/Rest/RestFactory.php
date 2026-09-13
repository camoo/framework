<?php

declare(strict_types=1);

namespace CAMOO\Model\Rest;

use CAMOO\Exception\Exception;
use CAMOO\Utils\Configure;

/**
 * Class RestFactory
 *
 * @author CamooSarl
 */
final class RestFactory
{
    private static ?RestFactory $_created = null;

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from RestFactory::create() instead
     */
    private function __construct()
    {
    }

    /** prevent the instance from being cloned (which would create a second instance of it) */
    private function __clone()
    {
    }

    /** prevent from being unserialized (which would create a second instance of it) */
    public function __wakeup(): void
    {
        throw new \LogicException('RestFactory cannot be unserialized.');
    }

    /** creates instances of Adapter Factory */
    public static function create(): RestFactory
    {
        static::$_created = new self();

        return static::$_created;
    }

    /**
     * @throws Exception
     *
     * @return AppRest
     */
    public function get(string $name): AppRest
    {
        $namespace = __NAMESPACE__ . '\\';
        $asNameSpace = explode('\\', $namespace);
        array_shift($asNameSpace);
        $nameSpace = '\\' . Configure::read('App.namespace') . '\\' . implode('\\', $asNameSpace);
        $class = $nameSpace . $name;
        if (!$this->classExists($class)) {
            throw new Exception(sprintf('Class %s not found !', $class));
        }

        return new $class();
    }

    /**
     * @param string $name class name
     *
     * @return bool
     */
    protected function classExists(string $name): bool
    {
        return class_exists($name);
    }
}
