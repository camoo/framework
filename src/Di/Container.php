<?php

declare(strict_types=1);

namespace CAMOO\Di;

use Ray\Di\InjectorInterface;
use Ray\Di\Name;

final class Container
{
    public function __construct(private InjectorInterface $injector)
    {
    }

    /**
     * Resolves and returns a class or interface instance.
     */
    public function get(string $id, string $name = Name::ANY): mixed
    {
        return CamooDi::get($id, $name);
    }

    /**
     * Checks if a class or interface exists.
     */
    public function has(string $id): bool
    {
        return class_exists($id) || interface_exists($id);
    }

    /**
     * Delegates calls to the underlying Ray\Di Injector.
     */
    public function __call(string $method, array $args): mixed
    {
        return call_user_func_array([$this->injector, $method], $args);
    }
}
