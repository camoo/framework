<?php

declare(strict_types=1);

namespace CAMOO\Template\Extension;

use CAMOO\Http\ServerRequest;

/**
 * Class Filters
 *
 * @author CamooSarl
 */
class Filters
{
    protected ServerRequest $request;

    public function __construct(private TwigHelper $baseHelper)
    {
        $this->request = $this->baseHelper->getRequest();
    }

    public function initialize(): void
    {
        //$this->baseHelper->loadFilter('Foo');
    }

    public function load(string $name): void
    {
        $this->baseHelper->loadFilter($name);
    }
}
