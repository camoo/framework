<?php

declare(strict_types=1);

namespace CAMOO\Template\Extension;

use CAMOO\Event\Event;
use CAMOO\Event\EventDispatcherInterface;
use CAMOO\Event\EventDispatcherTrait;
use CAMOO\Event\EventListenerInterface;
use CAMOO\Http\ServerRequest;
use Camoo\Inflector\Inflector;
use CAMOO\Interfaces\TemplateFunctionInterface;
use CAMOO\Utils\Configure;
use InvalidArgumentException;
use Twig\TwigFunction;

/**
 * Class FunctionHelper
 *
 * @author CamooSarl
 */
abstract class FunctionHelper implements TemplateFunctionInterface, EventListenerInterface, EventDispatcherInterface
{
    use EventDispatcherTrait;

    /** @var array $functions Functions to use in a helper */
    public array $functions = [];

    protected ServerRequest $request;

    public function __construct(private TwigHelper $baseHelper)
    {
        $this->getEventManager()->on($this);
        $this->request = $this->baseHelper->getRequest();
        $this->initialize();

        if (!empty($this->functions)) {
            foreach ($this->functions as $function) {
                $function = Inflector::classify($function);

                $namespace = __NAMESPACE__ . '\Functions\\';
                $class = $namespace . $function;

                if (!class_exists($class)) {
                    $asNameSpace = explode('\\', $namespace);
                    array_shift($asNameSpace);
                    $nameSpace = '\\' . Configure::read('App.namespace') . '\\' . implode('\\', $asNameSpace);
                    $class = $nameSpace . $function;
                    if (!class_exists($class)) {
                        throw new InvalidArgumentException(sprintf('Class %s not found !', $class));
                    }
                }

                $this->{$function} = new $class($baseHelper);
            }
        }
    }

    abstract public function getFunctions(): array;

    public function initialize(): void
    {
    }

    public function implementedEvents(): array
    {
        return [
            'FuncHelper.initialize' => 'beforeRender',
        ];
    }

    public function beforeRender(Event $event, string $function): void
    {
    }

    /** @param callable $callable */
    protected function add(string $name, $callable = null, array $options = [])
    {
        $this->dispatchEvent('FuncHelper.beforeRender', ['function' => $name]);

        return new TwigFunction($name, $callable, $options);
    }
}
