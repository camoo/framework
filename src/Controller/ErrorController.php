<?php

declare(strict_types=1);

namespace CAMOO\Controller;

/**
 * Class ErrorController
 *
 * @author CamooSarl
 */
class ErrorController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
    }

    public function overview()
    {
        $this->render();
    }
}
