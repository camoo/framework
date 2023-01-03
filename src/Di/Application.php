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
        $nameSpace = '\\' . Configure::read('App.namespace') . '\\Application';
        $class = sprintf('%s' . 'Modules' . '%s', $nameSpace, 'Collection');
        if (class_exists($class)) {
            $this->appApplication = $class;
        }
    }

    public function modules(ModuleCollection $modules): void
    {
        $application = new $this->appApplication();
        $application->dependencyInjectionModules($modules);
    }
}
