<?php
declare(strict_types=1);

namespace CAMOO\Http;

use \Middlewares\Utils\Dispatcher;
use GuzzleHttp\Psr7;
use CAMOO\Http\Response;
use CAMOO\Interfaces\ControllerInterface;
use CAMOO\Event\Event;
use CAMOO\Exception\Exception;
use CAMOO\Event\EventDispatcherTrait;

final class Caller
{
    use EventDispatcherTrait;

    protected $hRequest = [];
    public $controller = '\\App\\Controller\\PagesController';
    public $action = 'overview';
    public $plugin = null;
    public $xargs = [];
    public $uri = null;
    private $__controllerRaw = 'Pages';
    protected $sConfigDir;

    public function __construct($configDir)
    {
        $this->sConfigDir = $configDir;
        $this->initialize();
    }

    protected function initialize() : void
    {
        $this->bootstrap();
        $this->route();
    }

    public function bootstrap() : void
    {
        require_once $this->sConfigDir . '/bootstrap.php';
    }

    /**
     * @return ControllerInterface
     */
    public function getController(?Psr7\ServerRequest $request = null) : ControllerInterface
    {
        $oController = new $this->controller();
        $serverRequest =  new ServerRequest($request);
        $oController->request = $serverRequest;
        $oController->action = $this->action;
        $oController->controller = $this->__controllerRaw;
        $oController->setResponse(new Response());
        $oController->wakeUpController();
        return $oController;
    }

    public function dispatchRequest()
    {
        $dispatcher = \FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $r) {
            $r->addRoute($_SERVER['REQUEST_METHOD'], $this->uri, function ($request) {
                $controller = $this->getController($request);
                $event = new Event('AppController.initialize', $controller);
                $controller->getEventManager()->dispatch($event);

                $components = $controller->getComponentCollection();
                if (!empty($components)) {
                    foreach ($components as $value => $component) {
                        foreach ($component->implementedEvents() as $hook => $func) {
                            if ($func === 'beforeAction') {
                                $this->getEventManager()->on($component);
                                $this->dispatchEvent($hook);
                            }
                        }
                    }
                }

                if (!method_exists($controller, $this->action)) {
                    throw new Exception(sprintf('Action %s does not exist in %s', $this->action, get_class($controller)));
                }
                return call_user_func_array([$controller, $this->action], $this->xargs);
            });
        });

        $dispatcher = new Dispatcher([
            new \Middlewares\FastRoute($dispatcher),
            new \Middlewares\RequestHandler(),
        ]);

        return $dispatcher->dispatch(Psr7\ServerRequest::fromGlobals());
    }

    public function route() : void
    {
        require_once $this->sConfigDir . '/route.php';

        $this->uri = $uri = (new Psr7\Uri(getEnv('REQUEST_URI')))->getPath();

        $routeInfo = $oRouteDispatcher->dispatch(getEnv('REQUEST_METHOD'), $uri);

        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                $action = null;
                $asUri = explode("/", ltrim($uri, '/'));
                if (count($asUri) >= 2) {
                    list($controller, $action) = explode('/', ltrim($uri, '/'), 2);
                    array_shift($asUri);
                    array_shift($asUri);
                    if (!empty($action)) {
                        $this->xargs = $asUri;
                    }
                } else {
                    $controller = $asUri[0];
                }

                if (!empty($action)) {
                    if (strpos($action, '-') !== false) {
                        $action = \CAMOO\Utils\Inflector::camelize($action);
                    }
                    $this->action = trim($action,'/');
                }

                if ($controller) {
                    $this->__controllerRaw = ucfirst($controller);
                    $this->controller = '\\App\\Controller\\' .$this->__controllerRaw.'Controller';
                }

                $this->dispatchRequest();
                break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                break;
            case \FastRoute\Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                if (array_key_exists('controller', $handler)) {
                    $this->__controllerRaw = ucfirst($handler['controller']);
                    $this->controller = '\\App\\Controller\\' .$this->__controllerRaw.'Controller';
                }

                if (array_key_exists('action', $handler)) {
                    $this->action = trim($handler['action'],'/');
                }

                if (!empty($vars)) {
                    $this->xargs = $vars;
                }

                $this->dispatchRequest();
                break;
        }
    }
}
