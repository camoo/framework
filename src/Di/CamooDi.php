<?php

declare(strict_types=1);

namespace CAMOO\Di;

use Camoo\Cache\Cache;
use CAMOO\Di\Module\DefaultModule;
use CAMOO\Di\Module\ModuleCollection;
use CAMOO\Utils\Configure;
use const DIRECTORY_SEPARATOR;
use Ray\Compiler\DiCompiler;
use Ray\Di\Injector;
use Ray\Di\InjectorInterface;
use Ray\Di\Name;
use ReflectionClass;
use Throwable;

/**
 * @psalm-suppress DuplicateClass
 */
class CamooDi
{
    /** The current injector instance */
    protected static ?InjectorInterface $instance = null;

    /** The modules' collection to install in the injector */
    protected static ?DefaultModule $modules = null;

    /**
     * Creates a new injector instance
     *
     * @param array|callable|null $modules A list of modules to be installed. Or a callable
     *                                     that will return the list of modules.
     */
    public static function create(array|callable|null $modules = null): Injector
    {
        $modules = $modules ?? [];
        if (is_callable($modules)) {
            $modules = (array)$modules();
        }

        $diCacheDir = self::getCacheDir();
        $collection = new ModuleCollection($modules);
        $application = new Application();
        $application->modules($collection);
        $modules = new DefaultModule($collection);
        $tmpDir = rtrim($diCacheDir, DIRECTORY_SEPARATOR);
        $injector = new Injector($modules, $tmpDir);
        if (!self::$instance instanceof InjectorInterface) {
            static::container($injector);
            self::$modules = $modules;
        }

        return $injector;
    }

    /**
     * Get/Set the Injector instance.
     *
     * @param InjectorInterface|null $instance The injector to be used.
     */
    public static function container(?InjectorInterface $instance = null): InjectorInterface|Injector
    {
        if ($instance !== null) {
            static::$instance = $instance;
        }

        return static::$instance;
    }

    /**
     * Return an instance of a class after resolving its dependencies.
     *
     * @param string $class The class name or interface name to load.
     * @param string $name  The alias given to this class for namespacing the configuration.
     */
    public static function get(string $class, string $name = Name::ANY): mixed
    {
        try {
            return self::container()->getInstance($class, $name);
        } catch (Throwable) {
            return self::resolveUnTarget($class, $name);
        }
    }

    private static function resolveUnTarget(string $class, string $name = Name::ANY): mixed
    {
        $diCacheDir = self::getCacheDir();
        $collection = new ModuleCollection([]);
        $application = new Application();
        $application->modules($collection);
        $modules = new DefaultModule($collection);
        self::$modules = self::$modules ?? $modules;

        $unTargetedBind = new UnTargetedBind();
        $reflectionClass = new ReflectionClass($class);
        $method = $reflectionClass->getConstructor();

        $container = self::$modules->getContainer();
        $unTargetedBind($container, $method);

        $tmpDir = rtrim($diCacheDir, DIRECTORY_SEPARATOR);

        if (Cache::checks('camoo_di.instance', 'camoo_di')) {
            Cache::clears('camoo_di');
        }

        $injector = new Injector(self::$modules, $tmpDir);
        $containerInjector = self::container($injector);

        $compiler = new DiCompiler(self::$modules, $diCacheDir);
        $compiler->compileContainer();

        register_shutdown_function(
            fn () => Cache::writes('camoo_di.instance', $containerInjector, 'camoo_di')
        );

        return $compiler->getInstance($class, $name);
    }

    private static function getCacheDir(): string
    {
        if (!Configure::check('camoo_di')) {
            return CACHE . 'persistent/di/';
        }
        $diCacheDir = Configure::read('camoo_di');

        return $diCacheDir['path'] ?? CACHE . 'persistent/di/';
    }
}
