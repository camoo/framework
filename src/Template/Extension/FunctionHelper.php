<?php
declare(strict_types=1);

namespace CAMOO\Template\Extension;

use Twig\TwigFunction;
use CAMOO\Interfaces\TemplateFunctionInterface;
use CAMOO\Event\EventDispatcherInterface;
use CAMOO\Event\EventDispatcherTrait;
use CAMOO\Event\EventListenerInterface;
use CAMOO\Event\Event;

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

    public function __construct(TwigHelper $baseHelper)
    {
        $this->getEventManager()->on($this);
        $this->baseHelper = $baseHelper;
        $this->request = $baseHelper->getRequest();
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
