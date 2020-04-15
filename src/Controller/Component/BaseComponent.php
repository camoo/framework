<?php
declare(strict_types=1);

namespace CAMOO\Controller\Component;

use CAMOO\Interfaces\ComponentInterface;
use CAMOO\Interfaces\ControllerInterface;
use CAMOO\Utils\ConfigTrait;
use InvalidArgumentException;
use CAMOO\Utils\Inflector;

/**
 * Class BaseComponent
 * @author CamooSarl
 */
class BaseComponent implements ComponentInterface
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
            $this->{$component} = new $class($controller);
        }

        $this->initialize($config);
    }

    public function initialize(array $config=[])
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
}
