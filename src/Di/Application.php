<?php

declare(strict_types=1);

namespace CAMOO\Di;

use CAMOO\Di\Module\ModuleCollection;
use CAMOO\Utils\Configure;

final class Application
{
    private ?string $appApplication = null;

    public function __construct()
    {
        $class = '\\' . Configure::read('App.namespace') . '\\Application';
        if (class_exists($class)) {
            $this->appApplication = $class;
        }
    }

    public function modules(ModuleCollection $modules): void
    {
        if (null === $this->appApplication) {
            return;
        }
        $application = new $this->appApplication();
        $application->dependencyInjectionModules($modules);
    }
}
