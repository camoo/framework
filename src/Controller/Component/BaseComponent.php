<?php
declare(strict_types=1);

namespace CAMOO\Controller\Component;

use CAMOO\Interfaces\ComponentInterface;
use CAMOO\Interfaces\ControllerInterface;
use CAMOO\Utils\ConfigTrait;
use InvalidArgumentException;
use CAMOO\Utils\Inflector;
use CAMOO\Event\EventListenerInterface;

/**
 * Class BaseComponent
 * @author CamooSarl
 */
class BaseComponent implements ComponentInterface, EventListenerInterface
{
    use ConfigTrait;
    /** @var ControllerInterface $controller */
    private $controller;

    /**
     * Other Components this component uses.
     *
     * @var array
     */
    public $components = [];

    /**
     * Default config
     *
     * @var array
     */
    protected $_defaultConfig = [];

    public function __construct(?ControllerInterface $controller=null, array $config=[])
    {
        if (null !== $controller) {
            $this->setController($controller);
        }

        $this->setConfig($config);

        if (!empty($this->components)) {
            foreach ($this->components as $component) {
                $component = Inflector::classify($component);
                $class = sprintf($component.'%s', 'Component');

                if (!class_exists($class)) {
                    throw new InvalidArgumentException(sprintf('Class %s does not exist', $component));
                }
            }
            $controller->loadComponent($component);
            $this->{$component} = $controller->{$component};
        }

        $this->initialize($config);
    }

    public function initialize(array $config=[]) : void
    {
    }

    protected function setController(ControllerInterface $controller) : void
    {
        $this->controller = $controller;
    }

    protected function getController() : ControllerInterface
    {
        return $this->controller;
    }

    public function implementedEvents()
    {
        $eventMap = [
            'AppController.initialize'     => 'beforeAction',
            'AppController.beforeRender'   => 'beforeRender',
            'AppController.beforeRedirect' => 'beforeRedirect',
            'AppController.wakeUp'         => 'wakeUp',
        ];
        $events = [];
        foreach ($eventMap as $event => $method) {
            if (method_exists($this, $method)) {
                $events[$event] = $method;
            }
        }

        return $events;
    }
}
