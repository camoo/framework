<?php

declare(strict_types=1);

namespace CAMOO\Test\TestCase\Error;

use CAMOO\Error\ErrorHandler;
use CAMOO\Error\ExceptionRenderer;
use CAMOO\Http\ServerRequest;
use CAMOO\Utils\Configure;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ErrorHandler::class)]
#[CoversClass(ExceptionRenderer::class)]
class ErrorHandlerTest extends TestCase
{
    public function testExceptionRenderer(): void
    {
        Configure::write('debug', false);
        $request = new ServerRequest();
        $exception = new Exception('Not found error', 404);

        $renderer = new ExceptionRenderer($exception, $request);
        $this->assertInstanceOf(ExceptionRenderer::class, $renderer);
    }
}
