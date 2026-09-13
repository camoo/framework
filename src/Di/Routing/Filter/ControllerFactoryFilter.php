<?php

declare(strict_types=1);

namespace CAMOO\Di\Routing\Filter;

use CAMOO\Di\CamooDi;
use CAMOO\Interfaces\ControllerInterface;

/**
 * A dispatcher filter that builds the controller to dispatch
 * in the request.
 *
 * This filter resolves the request parameters into a controller
 * instance and attaches it to the event object.
 */
class ControllerFactoryFilter
{
    private readonly ControllerInterface $instance;

    public function __construct(private readonly string $controller)
    {
        $this->instance = CamooDi::get($this->controller);
    }

    public function getInstance(): ControllerInterface
    {
        return $this->instance;
    }
}
