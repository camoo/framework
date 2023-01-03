<?php

declare(strict_types=1);

namespace CAMOO\Controller;

use Cake\ORM\Locator\TableLocator;
use CAMOO\Controller\Component\ComponentCollection;
use CAMOO\Event\EventDispatcherInterface;
use CAMOO\Event\EventDispatcherTrait;
use CAMOO\Event\EventInterface;
use CAMOO\Event\EventListenerInterface;
use CAMOO\Exception\Exception;
use Camoo\Http\Curl\Domain\Entity\Stream;
use CAMOO\Http\ServerRequest;
use CAMOO\Interfaces\ControllerInterface;
use CAMOO\Model\Rest\RestLocatorTrait;
use CAMOO\Template\Extension\FilterCollection;
use CAMOO\Template\Extension\Filters\Flash;
use CAMOO\Template\Extension\FunctionCollection;
use CAMOO\Template\Extension\Functions\Form;
use CAMOO\Template\Extension\Functions\Html;
use CAMOO\Template\Extension\TwigHelper;
use CAMOO\Utils\Configure;
use CAMOO\Utils\Inflector;
use CAMOO\Validation\Adapters\Cake\Validator;
use JMS\Serializer\SerializerBuilder;
use Psr\Http\Message\ResponseInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TemplateWrapper;

abstract class AppController implements ControllerInterface, EventListenerInterface, EventDispatcherInterface
{
    use EventDispatcherTrait;
    use RestLocatorTrait;

    public ?string $controller = null;

    public ?string $action = null;

    public $Flash = null;

    public ?ServerRequest $request = null;

    protected array $defaultConfig = [];

    protected ?TemplateWrapper $oTemplate = null;

    protected ?Environment $oLayout = null;

    protected string $sTemplate = '%s/%s.tpl';

    protected string $sTemplateDir = 'Template';

    protected ?ResponseInterface $response = null;

    protected array $tplData = [];

    private ?ComponentCollection $componentCollection = null;

    private string $http_version = '1.1';

    public function __construct()
    {
        $this->getEventManager()->on($this);
    }

    public function wakeUpController(): void
    {
        $this->loadModel($this->controller);
        $this->componentCollection = new ComponentCollection($this);
        $this->initialize();

        $event = $this->dispatchEvent('AppController.initialize');
        if ($event->getResult() instanceof ResponseInterface) {
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
            $oTemplateLoader = new FilesystemLoader(APP . $this->sTemplateDir);
            $this->oLayout = new Environment($oTemplateLoader, ['cache' => TMP . 'cache' . DS . 'tpl']);
            $oFuncCollection = new FunctionCollection();
            $oFilterCollection = new FilterCollection();
            // check has Security Component
            $csrfSessionSegment = null;
            $csrf_Token = null;
            if ($this->hasComponent('Security') === true) {
                $oSecComponent = &$this->componentCollection['Security'];
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

        if ($event->getResult() instanceof ResponseInterface) {
            echo $event->getResult();
            $this->camooExit();
        }
    }

    /** Initializes the controller engine */
    public function initialize(): void
    {
    }

    public function implementedEvents(): array
    {
        return [
            'AppController.initialize' => 'beforeAction',
            'AppController.beforeRender' => 'beforeRender',
            'AppController.beforeRedirect' => 'beforeRedirect',
        ];
    }

    public function setResponse(ResponseInterface $response): AppController
    {
        $this->response = $response;

        $this->tplData[sprintf('%s_active', strtolower($this->controller))] = 'active';
        $this->tplData['page_title'] = ucfirst($this->controller);

        return $this;
    }

    /**
     * Sets variables to templates
     *
     * @param int|string|array|object|mixed|null $value
     */
    public function set(mixed $varName, mixed $value): void
    {
        if (empty($varName)) {
            throw new Exception('varName cannot be empty');
        }

        if ($varName === '_serialize') {
            $type = 'json';
            $serializer = SerializerBuilder::create()->build();
            $content = $serializer->serialize($value, $type);
            echo $content;
            exit;
        }
        if (!is_array($varName)) {
            $this->tplData[$varName] = $value;
        } else {
            $this->tplData = array_merge($this->tplData, $varName);
        }
    }

    /** Renders the template */
    public function render(): void
    {
        $this->loadActionTemplate();
        $event = $this->dispatchEvent('AppController.beforeRender');
        if ($event->getResult() instanceof ResponseInterface) {
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
        $this->setResponse($this->response->withBody(new Stream($contents)));

        echo $this->response->getBody();
        $this->camooExit();
    }

    public function beforeRender(EventInterface $event): void
    {
    }

    public function beforeAction(EventInterface $event): void
    {
    }

    public function beforeRedirect(EventInterface $event): void
    {
    }

    /**
     * @param string $destination URL to redirect to
     *
     * @throw Exception
     */
    public function redirect(string $destination, bool $permanent = false)
    {
        if (empty($destination)) {
            throw new Exception('destination cannot be empty');
        }

        if (mb_strpos($destination, '://') === false) {
            $this->dispatchEvent('AppController.beforeRedirect');

            $components = $this->getComponentCollection();
            if (!empty($components)) {
                foreach ($components as $component) {
                    foreach ($component->implementedEvents() as $hook => $func) {
                        if ($func === 'beforeRedirect') {
                            $this->getEventManager()->on($component);
                            $this->dispatchEvent($hook);
                        }
                    }
                }
            }

            if (empty(getenv('HTTPS')) || getenv('HTTPS') == 'off') {
                $protocol = 'http';
            } else {
                $protocol = 'https';
            }
            $destination = $protocol . '://' . getenv('HTTP_HOST') . $destination;
        }
        if ($permanent) {
            $code = 301;
            $message = $code . ' Moved Permanently';
        } else {
            $code = 302;
            $message = $code . ' Found';
        }
        header('HTTP/' . getenv('SERVER_PROTOCOL') . ' ' . $message, true, $code);
        header('Status: ' . $message, true, $code);
        header('Location: ' . $destination);
    }

    public function loadComponent(string $component, array $config = []): void
    {
        $component = Inflector::classify($component);
        $this->componentCollection->add($component, $config);
    }

    public function getComponentCollection(): ?ComponentCollection
    {
        return $this->componentCollection;
    }

    public function hasComponent(string $name): bool
    {
        return null !== $this->componentCollection && !empty($this->componentCollection[$name]);
    }

    protected function camooExit(): void
    {
        exit();
    }

    protected function loadModel(string $sModel): void
    {
        if (Configure::check('Database') === false) {
            return;
        }
        $this->{$sModel} = (new TableLocator())->get(Inflector::classify($sModel));
    }

    protected function loadRest(string $restModel): void
    {
        $this->{$restModel} = $this->getRestLocator()->get(Inflector::classify($restModel));
    }

    protected function _jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        $this->camooExit();
    }

    protected function showValidateErrors(Validator $model): void
    {
        $ahErrors = $model->getErrors();
        $asFields = [];
        if (!empty($ahErrors)) {
            foreach ($ahErrors as $sField => $ahError) {
                $asFields[] = $sField;
                foreach ($ahError as $sMessage) {
                    $this->request->Flash->error($sMessage);
                }
            }
            if (count($asFields) > 0) {
                $this->set('errorFields', $asFields);
            }
        }
    }

    protected function getReferer(): ?string
    {
        return $this->request->getReferer();
    }

    private function loadActionTemplate(): void
    {
        if ($this->oTemplate !== null) {
            return;
        }

        $this->oTemplate = $this->oLayout->load(
            sprintf(
                $this->sTemplate,
                $this->controller,
                Inflector::tableize($this->action)
            )
        );
    }
}
