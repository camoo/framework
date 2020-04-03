<?php
declare(strict_types=1);

namespace CAMOO\Model\Rest;

use CAMOO\Exception\Exception;
use CAMOO\Utils\Configure;

/**
 * Class RestFactory
 * @author CamooSarl
 */
final class RestFactory
{
    /** @var RestFactory $_created */
    private static $_created = null;

    /**
     * creates instances of Adapter Factory
     * @return RestFactory
     */
    public static function create() : RestFactory
    {
        static::$_created = new self;
        return static::$_created;
    }

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from RestFactory::create() instead
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
     * @param string $name
     * @return AppRest
     * @throws Exception
     */
    public function get(string $name)
    {
        $namespace = __NAMESPACE__. '\\';
        $asNameSpace = explode('\\', $namespace);
        array_shift($asNameSpace);
        $nameSpace = '\\' . Configure::read('App.namespace') .'\\'. implode('\\', $asNameSpace);
        $class = $nameSpace . $name;
        if (!$this->classExists($class)) {
            throw new Exception(sprintf('Class %s not found !', $class));
        }

        return new $class();
    }
}
