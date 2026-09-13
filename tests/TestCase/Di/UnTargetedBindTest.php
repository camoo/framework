<?php

namespace CAMOO\Test\TestCase\Di;

use CAMOO\Di\UnTargetedBind;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Ray\Di\Container;
use ReflectionMethod;

class DummyClassForBind
{
    public function __construct(DummyDependency $dep, string $unbound = '')
    {
    }
}

class DummyDependency
{
}

#[CoversClass(UnTargetedBind::class)]
class UnTargetedBindTest extends TestCase
{
    public function testInvoke(): void
    {
        $container = new Container();
        $binder = new UnTargetedBind();
        $refMethod = new ReflectionMethod(DummyClassForBind::class, '__construct');

        $binder($container, $refMethod);
        $this->assertTrue(true);
    }
}
