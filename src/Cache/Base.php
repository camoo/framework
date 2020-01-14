<?php

namespace CAMOO\Cache;

/**
 * Class Base
 * @author CamooSarl
 */
class Base
{

    /** @var array cache Factory */
    private $cacheFactory = [CacheSystemFactory::class, 'create'];

    /**
     * @return FileSystemFactoryInterface
     */
    protected function loadFactory()
    {
        return call_user_func($this->cacheFactory);
    }
}
