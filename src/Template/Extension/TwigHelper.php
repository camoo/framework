<?php
declare(strict_types=1);

namespace CAMOO\Template\Extension;

use Twig\Extension\AbstractExtension as BaseExtension;
use CAMOO\Http\ServerRequest;
use CAMOO\Template\Extension\FunctionCollection;
use CAMOO\Template\Extension\FilterCollection;
use CAMOO\Template\Extension\Functions as BaseFunction;
use CAMOO\Template\Extension\Filters as BaseFilter;
use CAMOO\Exception\Exception;
use CAMOO\Utils\Configure;

/**
 * Class TwigHelper
 * @author CamooSarl
 */
final class TwigHelper extends BaseExtension
{
    /** @var ServerRequest $request */
    private $request;

    /** @var FunctionCollection $functionCollection */
    private $functionCollection;

    /** @var FilterCollection $filterCollection */
    private $filterCollection;

    public function __construct(ServerRequest $request, FunctionCollection $function, FilterCollection $filter)
    {
        $this->request = $request;
        $this->functionCollection = $function;
        $this->filterCollection = $filter;
    }

    /**
     * @return ServerRequest
     */
    final public function getRequest() : ServerRequest
    {
        return $this->request;
    }

    /**
     * @param string|object $name
     * @return void
     */
    final public function loadFunction($name) : void
    {
        if (is_object($name)) {
            $this->functionCollection->add($name);
            return;
        }

        $namespace = __NAMESPACE__. '\Functions\\';
        $class = $namespace . $name;

        if (!class_exists($class)) {
            $asNameSpace = explode('\\', $namespace);
            array_shift($asNameSpace);
            $nameSpace = '\\' . Configure::read('App.namespace') .'\\'. implode('\\', $asNameSpace);
            $class = $nameSpace . $name;
            if (!class_exists($class)) {
                throw new Exception(sprintf('Class %s not found !', $class));
            }
        }
        $oClass = new $class($this);
        $this->functionCollection->add($oClass);
    }

    /**
     * @param string $name
     * @return void
     */
    final public function loadFilter(string $name) : void
    {
        $namespace = __NAMESPACE__. '\Filters\\';
        $class = $namespace . $name;

        if (!class_exists($class)) {
            $asNameSpace = explode('\\', $namespace);
            array_shift($asNameSpace);
            $nameSpace = '\\' . Configure::read('App.namespace') .'\\'. implode('\\', $asNameSpace);
            $class = $nameSpace . $name;
            if (!class_exists($class)) {
                throw new Exception(sprintf('Class %s not found !', $class));
            }
        }
        $oClass = new $class($this);

        $this->filterCollection->add($oClass);
    }

    /**
     * Initiliazes the TwigHelper engine
     *
     * @return void
     */
    final public function initialize() : void
    {
        $this->_initFunctions();
        $this->_initFiters();
    }

    private function _initFunctions() : void
    {
        $baseFunction = new BaseFunction($this);
        $baseFunction->initialize();
        $namespace = __NAMESPACE__. '\\AppFunctions';
        $asNameSpace = explode('\\', $namespace);
        array_shift($asNameSpace);
        $appFuncClass = '\\' . Configure::read('App.namespace') .'\\'. implode('\\', $asNameSpace);
        if (class_exists($appFuncClass)) {
            $oAppFuncClass = new $appFuncClass($this);
            $oAppFuncClass->initialize();
        }
    }

    private function _initFiters() : void
    {
        $baseFunction = new BaseFilter($this);
        $baseFunction->initialize();
        $namespace = __NAMESPACE__. '\\AppFilters';
        $asNameSpace = explode('\\', $namespace);
        array_shift($asNameSpace);
        $appFuncClass = '\\' . Configure::read('App.namespace') .'\\'. implode('\\', $asNameSpace);
        if (class_exists($appFuncClass)) {
            $oAppFuncClass = new $appFuncClass($this);
            $oAppFuncClass->initialize();
        }
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
}
