<?php

declare(strict_types=1);

namespace CAMOO\TestCase;

use CAMOO\Controller\AppController;
use CAMOO\Exception\Exception;
use CAMOO\Http\ServerRequest;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest as GuzzleServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Base test case for controller actions.
 *
 * Applications can extend this class to create controllers with a configured
 * request and response without duplicating framework bootstrapping code.
 */
abstract class ControllerTestCase extends TestCase
{
    protected function createController(
        string $controllerClass,
        string $action = 'index',
        string $method = 'GET',
        string $uri = '/',
        array $headers = [],
        string $body = '',
    ): AppController {
        $controller = new $controllerClass();
        if (!$controller instanceof AppController) {
            throw new Exception(sprintf('%s must extend %s.', $controllerClass, AppController::class));
        }

        $controller->controller = $this->controllerName($controllerClass);
        $controller->action = $action;
        $controller->request = new ServerRequest(
            new GuzzleServerRequest($method, $uri, $headers, $body),
        );
        $controller->setResponse(new Response());

        return $controller;
    }

    protected function dispatchAction(
        AppController $controller,
        ?string $action = null,
        array $arguments = [],
    ): ResponseInterface {
        $response = $controller->wakeUpController();
        if ($response instanceof ResponseInterface) {
            return $response;
        }

        $action ??= $controller->action;
        if ($action === null || !method_exists($controller, $action)) {
            throw new Exception(sprintf('Action %s does not exist in %s.', $action ?? '', $controller::class));
        }

        $response = $controller->{$action}(...$arguments);
        if (!$response instanceof ResponseInterface) {
            throw new Exception(sprintf('Action %s must return a PSR-7 response.', $action));
        }

        return $response;
    }

    private function controllerName(string $controllerClass): string
    {
        $shortName = (new \ReflectionClass($controllerClass))->getShortName();

        return str_ends_with($shortName, 'Controller')
            ? substr($shortName, 0, -strlen('Controller'))
            : $shortName;
    }
}
