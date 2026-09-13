<?php

declare(strict_types=1);

namespace CAMOO\Test\TestCase\Di;

use CAMOO\Di\Application;
use CAMOO\Di\Module\ModuleCollection;
use CAMOO\Utils\Configure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Application::class)]
class ApplicationTest extends TestCase
{
    public function testApplicationWithoutClass(): void
    {
        Configure::write('App.namespace', 'NonExistentNamespaceForTesting');
        $application = new Application();
        $modules = new ModuleCollection();
        $application->modules($modules);
        $this->assertTrue(true);
    }
}
