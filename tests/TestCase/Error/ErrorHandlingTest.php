<?php

namespace CAMOO\Test\TestCase\Error;

use CAMOO\Controller\ErrorController;
use CAMOO\Error\ErrorHandler;
use CAMOO\Error\ExceptionRenderer;
use CAMOO\Exception\Http\NotFoundException;
use CAMOO\Http\ServerRequest;
use CAMOO\Utils\Configure;
use GuzzleHttp\Psr7\ServerRequest as GuzzleRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Whoops\Exception\Inspector;
use Whoops\Handler\Handler;

#[CoversClass(ErrorHandler::class)]
#[CoversClass(ExceptionRenderer::class)]
#[CoversClass(ErrorController::class)]
class ErrorHandlingTest extends TestCase
{
    public function setUp(): void
    {
        if (!defined('LOGS')) {
            define('LOGS', TMP . 'logs' . DS);
        }
        if (!is_dir(LOGS)) {
            mkdir(LOGS, 0777, true);
        }
    }

    public function testExceptionRendererDebugEnabled(): void
    {
        Configure::write('debug', true);
        $request = new ServerRequest(new GuzzleRequest('GET', '/test-url'));
        $exception = new \Exception('Custom Error', 500);

        $renderer = new ExceptionRenderer($exception, $request);
        @$renderer->render();

        $this->assertInstanceOf(ErrorController::class, $renderer->controller);
    }

    public function testExceptionRendererDebugDisabledNotFoundException(): void
    {
        Configure::write('debug', false);
        $request = new ServerRequest(new GuzzleRequest('GET', '/test-url'));
        $exception = new NotFoundException('Page not found exception');

        $renderer = new ExceptionRenderer($exception, $request);
        @$renderer->render();

        $this->assertSame($exception, $renderer->error);
    }

    public function testExceptionRendererDebugDisabledGenericException(): void
    {
        Configure::write('debug', false);
        $request = new ServerRequest(new GuzzleRequest('GET', '/test-url'));
        $exception = new \Exception('Internal error message', 500);

        $renderer = new ExceptionRenderer($exception, $request);
        @$renderer->render();

        $refMethod = new \ReflectionMethod($renderer, '_getMessage');
        $refMethod->setAccessible(true);
        $msg = $refMethod->invoke($renderer);
        $this->assertSame(ExceptionRenderer::MSG_INTERNAL_ERR, $msg);
    }

    public function testExceptionRendererDebugDisabled400Exception(): void
    {
        Configure::write('debug', false);
        $request = new ServerRequest(new GuzzleRequest('GET', '/test-url'));
        $exception = new \Exception('Not found code', 400);

        $renderer = new ExceptionRenderer($exception, $request);
        $refMethod = new \ReflectionMethod($renderer, '_getMessage');
        $refMethod->setAccessible(true);
        $msg = $refMethod->invoke($renderer);
        $this->assertSame(ExceptionRenderer::MSG_NOT_FOUND, $msg);
    }

    public function testErrorHandlerInCli(): void
    {
        Configure::write('debug', true);
        $handler = new ErrorHandler();
        $handler->setException(new \Exception('Test Logged Exception'));
        $handler->setInspector(new Inspector(new \Exception('Test Logged Exception')));

        $result = $handler->handle();
        $this->assertSame(Handler::QUIT, $result);
        $this->assertFileExists(LOGS . 'cli-error.log');
    }
}
