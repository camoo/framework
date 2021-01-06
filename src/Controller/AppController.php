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
use CAMOO\Interfaces\ControllerInterface;
use CAMOO\Template\Extension\TwigHelper;
use CAMOO\Template\Extension\FunctionCollection;
use CAMOO\Template\Extension\FilterCollection;
use CAMOO\Template\Extension\Functions\Form;
use CAMOO\Template\Extension\Functions\Html;
use CAMOO\Template\Extension\Filters\Flash;
use CAMOO\Model\Rest\RestLocatorTrait;
use CAMOO\Controller\Component\ComponentCollection;
use CAMOO\Utils\Configure;

abstract class AppController implements ControllerInterface, EventListenerInterface, EventDispatcherInterface
{
    use EventDispatcherTrait;
    use RestLocatorTrait;

    protected $defaultConfig = [];

    /** @var CommandCollection $componentCollection */
    private $componentCollection = null;

    /** @var ControllerInterface $controller */
    public $controller = null;

    /** @var string $action */
    public $action = null;

    public $Flash = null;
    protected $oTemplate = null;
    protected $oLayout = null;
    protected $sTemplate = '%s/%s.tpl';
    protected $sTemplateDir = 'Template';

    /** @var \CAMOO\Http\ServerRequest $request */
    public $request = null;

    /** @var \CAMOO\Http\Response $response */
    protected $response = null;

    private $http_version = '1.1';

    protected $tplData = [];

    public function __construct()
    {
        $this->getEventManager()->on($this);
    }

    public function wakeUpController()
    {
        $this->loadModel($this->controller);
        $this->componentCollection = new ComponentCollection($this);
        $this->initialize();

        $event = $this->dispatchEvent('AppController.initialize');
        if ($event->getResult() instanceof Response) {
            echo $event->getResult();
            $this->camooExit();
        }

        $event = $this->dispatchEvent('AppController.wakeUp');
        $components = $this->getComponentCollection();
        if (!empty($components)) {
            foreach ($components as $value => $component) {
                foreach ($component->implementedEvents() as $hook => $func) {
                    if ($func === 'wakeUp') {
                        $this->getEventManager()->on($component);
                        $this->dispatchEvent($hook);
                    }
                }
            }
        }

        if ($this->oLayout === null) {
            $oTemplateLoader = new \Twig\Loader\FilesystemLoader(APP.$this->sTemplateDir);
            $this->oLayout = new \Twig\Environment($oTemplateLoader, ['cache' => TMP.'cache'. DS . 'tpl']);
            $oFuncCollection = new FunctionCollection();
            $oFilterCollection = new FilterCollection();
            // check has Security Component
            $csrfSessionSegment = null;
            $csrf_Token = null;
            if ($this->hasComponent('Security') === true) {
                $oSecComponent =& $this->componentCollection['Security'];
                $csrf_Token = $oSecComponent->csrf_Token;
                $csrfSessionSegment = $oSecComponent->csrfSessionSegment;
                unset($oSecComponent->csrfSessionSegment);
                unset($oSecComponent->csrf_Token);
            }

            $flashFilter = new Flash($this->request);
            $formHelper = new Form($this->request, $csrfSessionSegment, $csrf_Token);
            $htmlHelper = new Html($this->request);
            $extensions = new TwigHelper($this->request, $oFuncCollection, $oFilterCollection);
            $extensions->initialize();
            $extensions->loadFunction($formHelper);
            $extensions->loadFunction($htmlHelper);
            $extensions->loadFilter($flashFilter);
            $this->oLayout->addExtension($extensions);
        }

        if ($event->getResult() instanceof Response) {
            echo $event->getResult();
            $this->camooExit();
        }

        return null;
    }

    /**
     * Initiliazes the controller engine
     *
     * @return void
     */
    public function initialize() : void
    {
    }

    private function loadActionTemplate() : void
    {
        if ($this->oTemplate === null) {
            $this->oTemplate = $this->oLayout->load(sprintf($this->sTemplate, $this->controller, Inflector::tableize($this->action)));
        }
    }

    public function implementedEvents() : array
    {
        return [
            'AppController.initialize'     => 'beforeAction',
            'AppController.beforeRender'   => 'beforeRender',
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

        $this->tplData[sprintf('%s_active', strtolower($this->controller))] = 'active';
        $this->tplData['page_title'] = ucfirst($this->controller);
        return $this;
    }

    /**
     * Sets variables to templates
     *
     * @param string $varName
     * @param null|int|string|array|object|mixed $value
     * @return void
     */
    public function set(string $varName, $value) : void
    {
        if (empty($varName)) {
            throw new Exception('varName cannot be empty');
        }

        if ($varName === '_serialize') {
            $type = 'json';
            $serializer = \JMS\Serializer\SerializerBuilder::create()->build();
            $content = $serializer->serialize($value, $type);
            echo $content;
            exit;
        }
        if ($varName !== null) {
            $this->tplData[$varName] = $value;
        } else {
            $this->tplData = array_merge($this->tplData, $varName);
        }
    }

    /**
     * Renders the a template
     * @return void
     */
    public function render() : void
    {
        $this->loadActionTemplate();
        $event = $this->dispatchEvent('AppController.beforeRender');
        if ($event->getResult() instanceof Response) {
            echo $event->getResult();
            $this->camooExit();
        }


        $components = $this->getComponentCollection();
        if (!empty($components)) {
            foreach ($components as $value => $component) {
                foreach ($component->implementedEvents() as $hook => $func) {
                    if ($func === 'beforeRender') {
                        $this->getEventManager()->on($component);
                        $this->dispatchEvent($hook);
                    }
                }
            }
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
    public function beforeRender(Event $event)
    {
        return null;
    }

    /**
     * @param Event $event
     * @return null
     */
    public function beforeAction(Event $event)
    {
        return null;
    }

    /**
     * @param Event $event
     * @return null
     */
    public function beforeRedirect(Event $event)
    {
        return null;
    }

    protected function camooExit() : void
    {
        exit();
    }

    /**
     * @param  string $destination URL to redirect to
     * @param bool $permanent
     *
     * @throw Exception
     *
     * @return void
     */
    public function redirect(string $destination, bool $permanent = false) : void
    {
        if (empty($destination)) {
            throw new Exception('destination cannot be empty');
        }

        if (mb_strpos($destination, '://') === false) {
            $this->dispatchEvent('AppController.beforeRedirect');

            $components = $this->getComponentCollection();
            if (!empty($components)) {
                foreach ($components as $value => $component) {
                    foreach ($component->implementedEvents() as $hook => $func) {
                        if ($func === 'beforeRedirect') {
                            $this->getEventManager()->on($component);
                            $this->dispatchEvent($hook);
                        }
                    }
                }
            }

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

    /**
     * @param string $sModel
     * @return void
     */
    protected function loadModel(string $sModel) : void
    {
        if (Configure::check('Database') === false) {
            return;
        }
        $this->{$sModel} = (new TableLocator())->get(Inflector::classify($sModel));
    }

    /**
     * @param string $restModel
     * @return void
     */
    protected function loadRest(string $restModel) : void
    {
        $this->{$restModel} = $this->getRestLocator()->get(Inflector::classify($restModel));
    }

    protected function _jsonResponse(array $data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        $this->camooExit();
    }

    /**
     * @param object $ohEntity
     * @return void
     */
    protected function showValidateErrors($ohEntity)
    {
        $ahErrors = $ohEntity->getErrors();
        $asFields = [];
        if (!empty($ahErrors)) {
            foreach ($ahErrors as $sField => $ahError) {
                $asFields[] = $sField;
                foreach ($ahError as $sMessage) {
                    $this->request->Flash->error($sMessage);
                }
            }
            if (count($asFields)>0) {
                $this->set('errorFields', $asFields);
            }
        }
    }

    /**
     * @param string $component
     * @return void
     */
    public function loadComponent(string $component, array $config=[]) : void
    {
        $component = Inflector::classify($component);
        $this->componentCollection->add($component, $config);
    }

    public function getComponentCollection()
    {
        return $this->componentCollection;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasComponent(string $name) : bool
    {
        return !empty($this->componentCollection[$name]);
    }

    /**
     * @return null|string
     */
    protected function getReferer() : ?string
    {
        return $this->request->getReferer();
    }
}
