<?php
namespace CAMOO\Http;

use \GuzzleHttp\Psr7\ServerRequest;
use \Middlewares\Utils\Dispatcher;
use \CAMOO\Utils\QueryData;

require CORE_PATH . 'config' . DS . 'bootstrap.php';
class Caller
{
    protected $hRequest = [];
    public $controller = '\\App\\Controller\\PagesController';
    public $action = 'overview';
    public $plugin = null;
    public $xargs = [];
    public $uri = null;
    private $__controllerRaw = 'Pages';
    private $__session = [Session::class, 'create'];
    private $__flash = [Flash::class, 'create'];
    private $__cookie = [Cookie::class, 'create'];
    private $__queryData = [\CAMOO\Utils\QueryData::class, 'create'];

    public function __construct()
    {
        $hRquestUri = parse_url($_SERVER['REQUEST_URI']);
        parse_str($_SERVER['QUERY_STRING'], $hService);
        if (array_key_exists('query', $hRquestUri)) {
            parse_str($hRquestUri['query'], $hQuery);
        }
        $hQuery['caller_ip'] = $_SERVER['REMOTE_ADDR'];
        $this->hRequest = array_merge($hQuery, $hService);
        $this->route();
        ###########
        $this->initialize();
    }

    private function __getSession()
    {
        return call_user_func($this->__session);
    }

    private function __getFlash($oFlashSession)
    {
        return call_user_func($this->__flash, $oFlashSession);
    }

    private function __getCookie()
    {
        return call_user_func($this->__cookie);
    }


    private function __getQueryData($data)
    {
        return call_user_func($this->__queryData, $data);
    }

    protected function initialize()
    {
        $hRequest = $this->hRequest;
        if (!count($hRequest)) {
            header("HTTP/1.0 404 Not Found");
            die;
        }
    }

    public function getController($request = null)
    {
        $oController = new $this->controller();
        $oController->request = $request;
        $oController->request->session = $this->__getSession()->segment();
        $oController->request->csrf_Token = $this->__getSession()->getCsrfToken()->getValue();
        $oController->request->query = new QueryData($request->getQueryParams());
        $oController->request->data = new QueryData($request->getParsedBody());
        $oController->request->cookie = $this->__getCookie();
        $oController->action = $this->action;
        $oController->Flash = $this->__getFlash($this->__getSession()->getFlash())->initialize();
        $oController->controller = $this->__controllerRaw;
        $oController->initialize();
        return $oController;
    }

    public function dispatchRequest()
    {
        $dispatcher = \FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $r) {
            $r->addRoute($_SERVER['REQUEST_METHOD'], $this->uri, function ($request) {
                return call_user_func_array([$this->getController($request), $this->action], $this->xargs);
            });
        });
        $dispatcher = new Dispatcher([new \Middlewares\FastRoute($dispatcher), new \Middlewares\RequestHandler()]);
        return $dispatcher->dispatch(ServerRequest::fromGlobals());
    }

    public function route()
    {
        require CONFIG . 'route.php';

        #########
        // Fetch method and URI from somewhere
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];

        // Strip query string (?foo=bar) and decode URI
        if (false !== $pos = mb_strpos($uri, '?')) {
            $uri = mb_substr($uri, 0, $pos);
        }
        $this->uri = $uri = rawurldecode($uri);

        $routeInfo = $oRouteDispatcher->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                // ... 404 Not Found
                $action = null;
                $asUri = explode("/", ltrim($uri, '/'));
                if (count($asUri) > 2) {
                    list($controller, $action) = explode("/", ltrim($uri, '/'), 2);
                    array_shift($asUri);
                    array_shift($asUri);
                    if ($action) {
                        $this->xargs = $asUri;
                    }
                } else {
                    $controller = $asUri[0];
                }

                if ($action) {
                    $this->action = $action;
                }

                if ($controller) {
                    $this->__controllerRaw = ucfirst($controller);
                    $this->controller = '\\App\\Controller\\' .$this->__controllerRaw.'Controller';
                }

                ######
                $this->dispatchRequest();
                //echo $response->getBody();
                #die;
                ############
                break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                // ... 405 Method Not Allowed
                break;
            case \FastRoute\Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];

                if (array_key_exists('controller', $handler)) {
                    $this->__controllerRaw = ucfirst($handler['controller']);
                    $this->controller = '\\App\\Controller\\' .$this->__controllerRaw.'Controller';
                }

                if (array_key_exists('action', $handler)) {
                    $this->action = $handler['action'];
                }

                if ($vars) {
                    $this->xargs = $vars;
                }

                $this->dispatchRequest();
                break;
        }
    }
}
