<?php
declare(strict_types=1);

namespace CAMOO\Template\Extension;

use Twig\TwigFilter;
use CAMOO\Interfaces\TemplateFilterInterface;
use CAMOO\Event\EventDispatcherInterface;
use CAMOO\Event\EventDispatcherTrait;
use CAMOO\Event\EventListenerInterface;
use CAMOO\Event\Event;
use CAMOO\Utils\Inflector;
use InvalidArgumentException;
use CAMOO\Utils\Configure;

/**
 * Class FilterHelper
 * @author CamooSarl
 */
abstract class FilterHelper implements TemplateFilterInterface, EventListenerInterface, EventDispatcherInterface
{
    use EventDispatcherTrait;
    abstract public function getFilters() : array;

    /** @var TwigHelper */
    private $baseHelper;

    /** @var ServerRequest $request */
    protected $request;

    /** @var array $functions Functions to use in a helper */
    public $filters = [];

    public function __construct(TwigHelper $baseHelper)
    {
        $this->getEventManager()->on($this);
        $this->baseHelper = $baseHelper;
        $this->request = $baseHelper->getRequest();

        if (!empty($this->filters)) {
            foreach ($this->filters as $filter) {
                $filter = Inflector::classify($filter);

                $namespace = __NAMESPACE__. '\Extensions\\';
                $class = $namespace . $filter;

                if (!class_exists($class)) {
                    $asNameSpace = explode('\\', $namespace);
                    array_shift($asNameSpace);
                    $nameSpace = '\\' . Configure::read('App.namespace') .'\\'. implode('\\', $asNameSpace);
                    $class = $nameSpace . $filter;
                    if (!class_exists($class)) {
                        throw new InvalidArgumentException(sprintf('Class %s not found !', $class));
                    }
                }

                $this->{$filter} = new $class($baseHelper);
            }
        }
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
