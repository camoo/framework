<?php
declare(strict_types=1);

namespace CAMOO\Model\Rest;

use CAMOO\Interfaces\RestInterface;

/**
 * Trait RestLocatorTrait
 * @author CamooSarl
 */
trait RestLocatorTrait
{
    /** @var array */
    private $__restFactory = [RestFactory::class, 'create'];

    /**
     * gets Rest factory
     *
     * @return RestInterface
     */
    public function getRestLocator() : RestFactory
    {
        return call_user_func($this->__restFactory);
    }
}
