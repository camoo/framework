<?php

declare(strict_types=1);

namespace CAMOO\Controller\Component;

use CAMOO\Event\EventListenerInterface;
use Camoo\Inflector\Inflector;
use CAMOO\Interfaces\ComponentInterface;
use CAMOO\Interfaces\ControllerInterface;
use CAMOO\Utils\ConfigTrait;
use InvalidArgumentException;

/**
 * Class BaseComponent
 *
 * @author CamooSarl
 */
class BaseComponent implements ComponentInterface, EventListenerInterface
{
    use ConfigTrait;

    /**
     * Other Components this component uses.
     */
    public array $components = [];

    /**
     * Default config
     */
    protected array $_defaultConfig = [];

    private ControllerInterface $controller;

    public function __construct(?ControllerInterface $controller = null, array $config = [])
    {
        if (null !== $controller) {
            $this->setController($controller);
        }

        $this->setConfig($config);

        if (!empty($this->components)) {
            foreach ($this->components as $component) {
                $component = Inflector::classify($component);
                $class = sprintf($component . '%s', 'Component');

                if (!class_exists($class)) {
                    throw new InvalidArgumentException(sprintf('Class %s does not exist', $component));
                }
            }
            $controller->loadComponent($component);
            $this->{$component} = $controller->{$component};
        }

        $this->initialize($config);
    }

    public function initialize(array $config = []): void
    {
    }

    public function implementedEvents()
    {
        $eventMap = [
            'AppController.initialize' => 'beforeAction',
            'AppController.beforeRender' => 'beforeRender',
            'AppController.beforeRedirect' => 'beforeRedirect',
            'AppController.wakeUp' => 'wakeUp',
        ];
        $events = [];
        foreach ($eventMap as $event => $method) {
            if (method_exists($this, $method)) {
                $events[$event] = $method;
            }
        }

        return $events;
    }

    protected function setController(ControllerInterface $controller): void
    {
        $this->controller = $controller;
    }

    protected function getController(): ControllerInterface
    {
        return $this->controller;
    }
}
