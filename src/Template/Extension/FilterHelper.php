<?php
declare(strict_types=1);

namespace CAMOO\Template\Extension;

use Twig\TwigFilter;
use CAMOO\Interfaces\TemplateFilterInterface;
use CAMOO\Event\EventDispatcherInterface;
use CAMOO\Event\EventDispatcherTrait;
use CAMOO\Event\EventListenerInterface;
use CAMOO\Event\Event;

/**
 * Class FilterHelper
 * @author CamooSarl
 */
abstract class FilterHelper implements TemplateFilterInterface,  EventListenerInterface, EventDispatcherInterface
{
    use EventDispatcherTrait;
    abstract public function getFilters() : array;

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
        $this->dispatchEvent('FilterHelper.beforeRender', ['filter' => $name]);
        return new TwigFilter($name, $callable, $options);
    }

    public function implementedEvents() : array
    {
        return [
            'FilterHelper.initialize' => 'beforeRender',
        ];
    }

    /**
     * @param Event $event
     * @return void
     */
    public function beforeRender(Event $event, string $filter) : void
    {
    }
}
