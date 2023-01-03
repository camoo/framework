<?php

declare(strict_types=1);

namespace CAMOO\Di;

use function class_exists;
use function in_array;
use Ray\Di\Argument;
use Ray\Di\Bind;
use Ray\Di\Container;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

final class UnTargetedBind
{
    public function __invoke(Container $container, ?ReflectionMethod $method = null): void
    {
        $parameters = $method->getParameters();
        foreach ($parameters as $parameter) {
            $this->addConcreteClass($container, $parameter);
        }
    }

    private function addConcreteClass(Container $container, ReflectionParameter $parameter): void
    {
        $class = $this->getType($parameter);

        if (class_exists($class)) {
            $bind = new Bind($container, $class);
            $container->add($bind);
        }
    }

    private function getType(ReflectionParameter $parameter): string
    {
        $type = $parameter->getType();

        return $type instanceof ReflectionNamedType && !in_array(
            $type->getName(),
            Argument::UNBOUND_TYPE,
            true
        ) ? $type->getName() : '';
    }
}
