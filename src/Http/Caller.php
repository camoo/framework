<?php

declare(strict_types=1);

namespace CAMOO\Http;

use CAMOO\Controller\AppController;
use CAMOO\Di\Routing\Filter\ControllerFactoryFilter;
use CAMOO\Event\EventDispatcherTrait;
use CAMOO\Exception\Exception;
use Camoo\Http\Curl\Domain\Entity\Stream;
use Camoo\Http\Curl\Domain\Entity\Uri;
use Camoo\Http\Curl\Infrastructure\Response;
use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteCollector;

use function FastRoute\simpleDispatcher;

use GuzzleHttp\Psr7;
use Middlewares\FastRoute;
use Middlewares\RequestHandler;
use Middlewares\Utils\Dispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

final class Caller
{
    use EventDispatcherTrait;

    public string $controller = '\\App\\Controller\\PagesController';

    public string $action = 'overview';

    public $plugin = null;

    public array $xargs = [];

    public ?string $uri = null;

    protected array $hRequest = [];

    private string $controllerName = 'Pages';

    private ?ResponseInterface $response = null;

    /** @var list<MiddlewareInterface> */
    private array $middlewares = [];

    /** @param list<MiddlewareInterface> $middlewares */
    public function __construct(protected string $sConfigDir, array $middlewares = [])
    {
        $this->middlewares = $middlewares;
        $this->initialize();
    }

    public function bootstrap(): void
    {
        require_once $this->sConfigDir . '/bootstrap.php';
    }

    public function getResponse(): ResponseInterface
    {
        if ($this->response === null) {
            throw new Exception('No response has been dispatched.');
        }

        return $this->response;
    }

    public function getController(?ServerRequestInterface $request = null): AppController
    {
        $filter = new ControllerFactoryFilter($this->controller);
        $oController = $filter->getInstance();
        if (!$oController instanceof AppController) {
            throw new Exception(sprintf('%s must extend %s.', $oController::class, AppController::class));
        }
        $serverRequest = new ServerRequest($request);
        $oController->request = $serverRequest;
        $oController->action = $this->action;
        $oController->controller = $this->controllerName;
        $oController->setResponse(new Response(body: new Stream('')));
        return $oController;
    }

    public function dispatchRequest(): ResponseInterface
    {
        $dispatcher = simpleDispatcher(function (RouteCollector $routeCollector) {
            $requestType = $_SERVER['REQUEST_METHOD'] ?? getenv('REQUEST_METHOD') ?: 'GET';
            $routeCollector->addRoute(
                $requestType,
                $this->uri,
                function (ServerRequestInterface $request) {
                    $controller = $this->getController($request);
                    $response = $controller->wakeUpController();
                    if ($response instanceof ResponseInterface) {
                        return $response;
                    }

                    $components = $controller->getComponentCollection();
                    if (!empty($components)) {
                        foreach ($components as $component) {
                            foreach ($component->implementedEvents() as $hook => $func) {
                                if ($func === 'beforeAction') {
                                    $this->getEventManager()->on($component);
                                    $this->dispatchEvent($hook);
                                }
                            }
                        }
                    }

                    if (!method_exists($controller, $this->action)) {
                        throw new Exception(sprintf(
                            'Action %s does not exist in %s',
                            $this->action,
                            $controller::class,
                        ));
                    }

                    $result = call_user_func_array([$controller, $this->action], $this->xargs);

                    return $result instanceof ResponseInterface ? $result : throw new Exception(
                        sprintf('Action %s must return a PSR-7 response.', $this->action),
                    );
                },
            );
        });

        $dispatcher = new Dispatcher(array_merge($this->middlewares, [
            new FastRoute($dispatcher),
            new RequestHandler(),
        ]));

        return $dispatcher->dispatch(Psr7\ServerRequest::fromGlobals());
    }

    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    public function route(): ResponseInterface
    {
        $dispatcher = require_once $this->sConfigDir . '/route.php';
        /** @var GroupCountBased $routeDispatcher */
        $routeDispatcher = $dispatcher[0];
        $requestUri = $_SERVER['REQUEST_URI'] ?? getenv('REQUEST_URI') ?: '/';
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? getenv('REQUEST_METHOD') ?: 'GET';
        $this->uri = $uri = new Uri($requestUri)->getPath();

        $routeInfo = $routeDispatcher->dispatch($requestMethod, $uri);

        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                return $this->response = new Response(body: new Stream(''), statusCode: 404);
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                return $this->response = new Response(body: new Stream(''), statusCode: 405);
            case \FastRoute\Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                if (array_key_exists('controller', $handler)) {
                    $this->controllerName = ucfirst($handler['controller']);
                    $this->controller = '\\App\\Controller\\' . $this->controllerName . 'Controller';
                }

                if (array_key_exists('action', $handler)) {
                    $this->action = trim($handler['action'], '/');
                }

                if (!empty($vars)) {
                    $this->xargs = $vars;
                }

                return $this->response = $this->dispatchRequest();
        }

        return $this->response = new Response(body: new Stream(''), statusCode: 404);
    }

    protected function initialize(): ResponseInterface
    {
        $this->bootstrap();

        return $this->route();
    }
}
