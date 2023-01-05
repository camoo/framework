<?php

declare(strict_types=1);

namespace CAMOO\Http;

use CAMOO\Di\Routing\Filter\ControllerFactoryFilter;
use CAMOO\Event\Event;
use CAMOO\Event\EventDispatcherTrait;
use CAMOO\Exception\Exception;
use Camoo\Http\Curl\Domain\Entity\Uri;
use Camoo\Http\Curl\Infrastructure\Response;
use Camoo\Inflector\Inflector;
use CAMOO\Interfaces\ControllerInterface;
use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use GuzzleHttp\Psr7;
use Middlewares\FastRoute;
use Middlewares\RequestHandler;
use Middlewares\Utils\Dispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class Caller
{
    use EventDispatcherTrait;

    public string $controller = '\\App\\Controller\\PagesController';

    public string $action = 'overview';

    public $plugin = null;

    public array $xargs = [];

    public ?string $uri = null;

    protected array $hRequest = [];

    protected string $sConfigDir;

    private string $controllerName = 'Pages';

    public function __construct(string $configDir)
    {
        $this->sConfigDir = $configDir;
        $this->initialize();
    }

    public function bootstrap(): void
    {
        require_once $this->sConfigDir . '/bootstrap.php';
    }

    public function getController(?ServerRequestInterface $request = null): ControllerInterface
    {
        $filter = new ControllerFactoryFilter($this->controller);
        $oController = $filter->getInstance();
        $serverRequest = new ServerRequest($request);
        $oController->request = $serverRequest;
        $oController->action = $this->action;
        $oController->controller = $this->controllerName;
        $oController->setResponse(new Response());
        $oController->wakeUpController();

        return $oController;
    }

    public function dispatchRequest(): ResponseInterface
    {
        $dispatcher = simpleDispatcher(function (RouteCollector $routeCollector) {
            $requestType = $_SERVER['REQUEST_METHOD'];
            $routeCollector->addRoute(
                $requestType,
                $this->uri,
                function (ServerRequestInterface $request) {
                    $controller = $this->getController($request);
                    $event = new Event('AppController.initialize', $controller);
                    $controller->getEventManager()->dispatch($event);

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
                            get_class($controller)
                        ));
                    }

                    return call_user_func_array([$controller, $this->action], $this->xargs);
                }
            );
        });

        $dispatcher = new Dispatcher([
            new FastRoute($dispatcher),
            new RequestHandler(),
        ]);

        return $dispatcher->dispatch(Psr7\ServerRequest::fromGlobals());
    }

    public function route(): void
    {
        $dispatcher = require_once $this->sConfigDir . '/route.php';
        /** @var GroupCountBased $routeDispatcher */
        $routeDispatcher = $dispatcher[0];
        $this->uri = $uri = (new Uri(getenv('REQUEST_URI')))->getPath();

        $routeInfo = $routeDispatcher->dispatch(getenv('REQUEST_METHOD'), $uri);

        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                $action = null;
                $asUri = explode('/', ltrim($uri, '/'));
                if (count($asUri) >= 2) {
                    [$controller, $action] = explode('/', ltrim($uri, '/'), 2);
                    array_shift($asUri);
                    array_shift($asUri);
                    if (!empty($action)) {
                        $this->xargs = $asUri;
                    }
                } else {
                    $controller = $asUri[0];
                }

                if (!empty($action)) {
                    if (str_contains($action, '-')) {
                        $action = Inflector::camelize($action);
                    }
                    $this->action = trim($action, '/');
                }

                if ($controller) {
                    $this->controllerName = ucfirst($controller);
                    $this->controller = '\\App\\Controller\\' . $this->controllerName . 'Controller';
                }

                $this->dispatchRequest();
                break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                break;
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

                $this->dispatchRequest();
                break;
        }
    }

    protected function initialize(): void
    {
        $this->bootstrap();
        $this->route();
    }
}
