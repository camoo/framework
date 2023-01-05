<?php

declare(strict_types=1);

namespace CAMOO\Template\Extension;

use CAMOO\Event\Event;
use CAMOO\Event\EventDispatcherInterface;
use CAMOO\Event\EventDispatcherTrait;
use CAMOO\Event\EventListenerInterface;
use CAMOO\Http\ServerRequest;
use Camoo\Inflector\Inflector;
use CAMOO\Interfaces\TemplateFilterInterface;
use CAMOO\Utils\Configure;
use InvalidArgumentException;
use Twig\TwigFilter;

/**
 * Class FilterHelper
 *
 * @author CamooSarl
 */
abstract class FilterHelper implements TemplateFilterInterface, EventListenerInterface, EventDispatcherInterface
{
    use EventDispatcherTrait;

    public array $filters = [];

    protected ServerRequest $request;

    public function __construct(private TwigHelper $baseHelper)
    {
        $this->getEventManager()->on($this);
        $this->request = $this->baseHelper->getRequest();

        if (!empty($this->filters)) {
            foreach ($this->filters as $filter) {
                $filter = Inflector::classify($filter);

                $namespace = __NAMESPACE__ . '\Extensions\\';
                $class = $namespace . $filter;

                if (!class_exists($class)) {
                    $asNameSpace = explode('\\', $namespace);
                    array_shift($asNameSpace);
                    $nameSpace = '\\' . Configure::read('App.namespace') . '\\' . implode('\\', $asNameSpace);
                    $class = $nameSpace . $filter;
                    if (!class_exists($class)) {
                        throw new InvalidArgumentException(sprintf('Class %s not found !', $class));
                    }
                }

                $this->{$filter} = new $class($baseHelper);
            }
        }
    }

    abstract public function getFilters(): array;

    public function implementedEvents(): array
    {
        return [
            'FilterHelper.initialize' => 'beforeRender',
        ];
    }

    public function beforeRender(Event $event, string $filter): void
    {
    }

    protected function add(string $name, ?callable $callable = null, array $options = []): TwigFilter
    {
        $this->dispatchEvent('FilterHelper.beforeRender', ['filter' => $name]);

        return new TwigFilter($name, $callable, $options);
    }
}
