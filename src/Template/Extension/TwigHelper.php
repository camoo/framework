<?php

declare(strict_types=1);

namespace CAMOO\Template\Extension;

use CAMOO\Exception\Exception;
use CAMOO\Http\ServerRequest;
use CAMOO\Template\Extension\Filters as BaseFilter;
use CAMOO\Template\Extension\Functions as BaseFunction;
use CAMOO\Utils\Configure;
use Twig\Extension\AbstractExtension as BaseExtension;

/**
 * Class TwigHelper
 *
 * @author CamooSarl
 */
final class TwigHelper extends BaseExtension
{
    public function __construct(
        private ServerRequest $request,
        private FunctionCollection $functionCollection,
        private FilterCollection $filterCollection
    ) {
    }

    final public function getRequest(): ServerRequest
    {
        return $this->request;
    }

    /** @param string|object $name */
    final public function loadFunction(mixed $name): void
    {
        if (is_object($name)) {
            $this->functionCollection->add($name);

            return;
        }

        if (class_exists($name)) {
            $oClass = new $name($this);
            $this->functionCollection->add($oClass);

            return;
        }

        $namespace = __NAMESPACE__ . '\Functions\\';

        $class = $this->getAppExtension($namespace, $name);

        $oClass = new $class($this);
        $this->functionCollection->add($oClass);
    }

    /** @param string|object $name */
    final public function loadFilter(mixed $name): void
    {
        if (is_object($name)) {
            $this->filterCollection->add($name);

            return;
        }
        if (class_exists($name)) {
            $oClass = new $name($this);
            $this->filterCollection->add($oClass);

            return;
        }
        $namespace = __NAMESPACE__ . '\Filters\\';

        $class = $this->getAppExtension($namespace, $name);

        $oClass = new $class($this);

        $this->filterCollection->add($oClass);
    }

    /** Initializes the TwigHelper engine */
    final public function initialize(): void
    {
        $this->initializeFunctions();
        $this->initializeFilters();
    }

    public function getFunctions()
    {
        $ahFunctions = [];
        foreach ($this->functionCollection as $func) {
            $ahFunctions[] = $func;
        }

        return $ahFunctions;
    }

    public function getFilters()
    {
        $ahFilters = [];
        foreach ($this->filterCollection as $filter) {
            $ahFilters[] = $filter;
        }

        return $ahFilters;
    }

    private function initializeFunctions(): void
    {
        $baseFunction = new BaseFunction($this);
        $baseFunction->initialize();
        $namespace = __NAMESPACE__ . '\\AppFunctions';
        $asNameSpace = explode('\\', $namespace);
        array_shift($asNameSpace);
        $appFuncClass = '\\' . Configure::read('App.namespace') . '\\' . implode('\\', $asNameSpace);
        if (!class_exists($appFuncClass)) {
            return;
        }
        $oAppFuncClass = new $appFuncClass($this);
        $oAppFuncClass->initialize();
    }

    private function initializeFilters(): void
    {
        $baseFunction = new BaseFilter($this);
        $baseFunction->initialize();
        $namespace = __NAMESPACE__ . '\\AppFilters';
        $asNameSpace = explode('\\', $namespace);
        array_shift($asNameSpace);
        $appFuncClass = '\\' . Configure::read('App.namespace') . '\\' . implode('\\', $asNameSpace);

        if (!class_exists($appFuncClass)) {
            return;
        }
        $filters = new $appFuncClass($this);
        $filters->initialize();
    }

    private function getAppExtension(string $namespace, string $name): string
    {
        $class = $namespace . $name;
        if (class_exists($class)) {
            return $class;
        }

        $asNameSpace = explode('\\', $namespace);
        array_shift($asNameSpace);
        $nameSpace = '\\' . Configure::read('App.namespace') . '\\' . implode('\\', $asNameSpace);
        $class = $nameSpace . $name;
        if (!class_exists($class)) {
            throw new Exception(sprintf('Class %s not found !', $class));
        }

        return $class;
    }
}
