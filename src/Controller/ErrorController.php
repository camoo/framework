<?php

declare(strict_types=1);

namespace CAMOO\Controller;

use Psr\Http\Message\ResponseInterface;

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

    public function overview(): ResponseInterface
    {
        return $this->render();
    }
}
