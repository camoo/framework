<?php

namespace CAMOO\Cache;

use CAMOO\Interfaces\CacheSystemFactoryInterface;

/**
 * Class Base
 *
 * @author CamooSarl
 */
class Base
{
    /** @var array cache Factory */
    private $cacheFactory = [CacheSystemFactory::class, 'create'];

    protected function loadFactory(): CacheSystemFactoryInterface
    {
        return call_user_func($this->cacheFactory);
    }
}
