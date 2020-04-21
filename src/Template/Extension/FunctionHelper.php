<?php
declare(strict_types=1);

namespace CAMOO\Template\Extension;

use Twig\TwigFunction;
use CAMOO\Interfaces\TemplateFunctionInterface;
use CAMOO\Event\EventDispatcherInterface;
use CAMOO\Event\EventDispatcherTrait;
use CAMOO\Event\EventListenerInterface;
use CAMOO\Event\Event;
use CAMOO\Utils\Inflector;
use InvalidArgumentException;
use CAMOO\Utils\Configure;

/**
 * Class FunctionHelper
 * @author CamooSarl
 */
abstract class FunctionHelper implements TemplateFunctionInterface, EventListenerInterface, EventDispatcherInterface
{
    use EventDispatcherTrait;
    abstract public function getFunctions() : array;

    /** @var TwigHelper */
    private $baseHelper;

    /** @var ServerRequest $request */
    protected $request;

    /** @var array $functions Functions to use in a helper */
    public $functions = [];

    public function __construct(TwigHelper $baseHelper)
    {
        $this->getEventManager()->on($this);
        $this->baseHelper = $baseHelper;
        $this->request = $baseHelper->getRequest();
        $this->initialize();

        if (!empty($this->functions)) {
            foreach ($this->functions as $function) {
                $function = Inflector::classify($function);

                $namespace = __NAMESPACE__. '\Functions\\';
                $class = $namespace . $function;

                if (!class_exists($class)) {
                    $asNameSpace = explode('\\', $namespace);
                    array_shift($asNameSpace);
                    $nameSpace = '\\' . Configure::read('App.namespace') .'\\'. implode('\\', $asNameSpace);
                    $class = $nameSpace . $function;
                    if (!class_exists($class)) {
                        throw new InvalidArgumentException(sprintf('Class %s not found !', $class));
                    }
                }

                $this->{$function} = new $class($baseHelper);
            }
        }
    }

    public function initialize() : void
    {
    }

    /**
     * @param string $name
     * @param callable $callable
     * @param array $option
     */
    protected function add(string $name, $callable = null, array $options = [])
    {
        $this->dispatchEvent('FuncHelper.beforeRender', ['function' => $name]);
        return new TwigFunction($name, $callable, $options);
    }

    public function implementedEvents() : array
    {
        return [
            'FuncHelper.initialize' => 'beforeRender',
        ];
    }

    /**
     * @param Event $event
     * @return void
     */
    public function beforeRender(Event $event, string $function) : void
    {
    }
}
