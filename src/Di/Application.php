<?php

declare(strict_types=1);

namespace CAMOO\Di;

use CAMOO\Di\Module\ModuleCollection;
use CAMOO\Di\Routing\Filter\ControllerCollection;

final class Application
{
    public function modules(ModuleCollection $modules): void
    {
    }

    public function controllers(ControllerCollection $controllers): void
    {
    }
}
