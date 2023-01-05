<?php

declare(strict_types=1);

namespace CAMOO\Template\Extension;

use CAMOO\Http\ServerRequest;

/**
 * Class Functions
 *
 * @author CamooSarl
 */
class Functions
{
    protected ServerRequest $request;

    public function __construct(private TwigHelper $baseHelper)
    {
        $this->request = $this->baseHelper->getRequest();
    }

    public function initialize(): void
    {
    }

    public function load(string $name): void
    {
        $this->baseHelper->loadFunction($name);
    }
}
