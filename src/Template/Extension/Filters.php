<?php
declare(strict_types=1);

namespace CAMOO\Template\Extension;

/**
 * Class Filters
 * @author CamooSarl
 */
class Filters
{

    /** @var TwigHelper */
    private $baseHelper;

    /** @var ServerRequest $request */
    protected $request;

    public function __construct(TwigHelper $baseHelper)
    {
        $this->baseHelper = $baseHelper;
        $this->request = $baseHelper->getRequest();
    }

    public function initialize() : void
    {
        //$this->baseHelper->loadFilter('Foo');
    }

    public function load(string $name)
    {
        $this->baseHelper->loadFilter($name);
    }
}
