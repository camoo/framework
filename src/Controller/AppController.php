<?php
declare(strict_types=1);
namespace CAMOO\Controller;

use Cake\ORM\Locator\TableLocator;
use CAMOO\Utils\Inflector;
use CAMOO\Http\Response;
use CAMOO\Event\Event;
use CAMOO\Event\EventDispatcherInterface;
use CAMOO\Event\EventDispatcherTrait;
use CAMOO\Event\EventListenerInterface;

class AppController implements EventListenerInterface, EventDispatcherInterface
{
    use EventDispatcherTrait;
    public $controller = null;
    public $action = null;
    public $Flash = null;
    protected $oTemplate = null;
    protected $oLayout = null;
    protected $sTemplate = '%s/%s.tpl';
    protected $sTemplateDir = 'Templates';
    protected $sLayout = 'Templates/Layouts/default.tpl';
    public $request = null;
    protected $response = null;
    private $http_version = '1.1';
    protected $tplData = [];
    protected $__sessionRaw = [\CAMOO\Http\Session::class, 'create'];

    public function __construct()
    {
        $this->getEventManager()->on($this);
    }

    private function __getSessionRaw()
    {
        return call_user_func($this->__sessionRaw);
    }

    /**
     * Initiliazes the controller engine
     *
     * @return void
     */
    public function initialize()
    {
        if ($this->oLayout === null) {
            $oTemplateLoader = new \Twig\Loader\FilesystemLoader(APP.$this->sTemplateDir);
            $this->oLayout = new \Twig\Environment($oTemplateLoader, ['cache' => TMP.'cache'. DS . 'tpl']);
        }

        if ($this->oTemplate === null) {
            $this->oTemplate = $this->oLayout->load(sprintf($this->sTemplate, $this->controller, Inflector::tableize($this->action)));
        }

        // @See https://github.com/auraphp/Aura.Session
        if (in_array(getEnv('REQUEST_METHOD'), ['DELETE', 'POST', 'PUT'])) {
            $csrf_value = $_POST['__csrf_Token'];
            $oCsrfToken = $this->__getSessionRaw()->getCsrfToken();
            if (! $oCsrfToken->isValid($csrf_value)) {
                throw \CAMOO\Exception\Exception("Request Blackholed.");
            }
        }
        $this->loadModel($this->controller);
    }

    public function implementedEvents() : array
    {
        return [
            'AppController.initialize' => 'beforeRunning',
            'AppController.beforeRender' => 'beforeRender',
            'AppController.beforeRedirect' => 'beforeRedirect',
        ];
    }

    /**
     * @param Response $response
     * @return AppController
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Sets variables to templates
     *
     * @param mixed|string $varName
     * @param mixed $value
     * @return \CAMOO\Controller\AppController
     */
    public function set(string $varName, $value = null)
    {
        if ($varName !== null) {
            $this->tplData[$varName] = $value;
        } else {
            $this->tplData = array_merge($this->tplData, $varName);
        }
        $this->tplData['__csrf_Token'] = $this->request->csrf_Token;
        return $this;
    }

    /**
     * Renders the a template
     * @return void
     */
    public function render() : void
    {
        $event = $this->dispatchEvent('AppController.beforeRender');
        if ($event->getResult() instanceof Response) {
            echo $event->getResult();
            $this->camooExit();
        }

        $contents = $this->oTemplate->render($this->tplData);
        $this->setResponse($this->response->withStringBody($contents));

        echo $this->response;
        $this->camooExit();
    }

    /**
     * @param Event $event
     * @return null
     */
    public function beforeRender(Event $event) :?Response
    {
        return null;
    }

    /**
     * @param Event $event
     * @return null
     */
    public function beforeRedirect(Event $event) :?Response
    {
        return null;
    }

    protected function camooExit()
    {
        exit();
    }

    /**
     * @param  string $destination URL to redirect to
     */
    public function redirect($destination, $permanent = false)
    {
        if (mb_strpos($destination, '://') === false) {
            $this->dispatchEvent('AppController.beforeRedirect');

            if (empty(getEnv('HTTPS')) || getEnv('HTTPS') == 'off') {
                $protocol = 'http';
            } else {
                $protocol = 'https';
            }
            $destination = $protocol . '://' . getEnv('HTTP_HOST') . $destination;
        }
        if ($permanent) {
            $code    = 301;
            $message = $code . ' Moved Permanently';
        } else {
            $code    = 302;
            $message = $code . ' Found';
        }
        header('HTTP/'.getEnv('SERVER_PROTOCOL').' ' . $message, true, $code);
        header('Status: '  . $message, true, $code);
        header('Location: ' . $destination);
    }

    protected function loadModel($sModel)
    {
        $this->{$sModel} = (new TableLocator())->get(Inflector::classify($sModel));
    }
}
