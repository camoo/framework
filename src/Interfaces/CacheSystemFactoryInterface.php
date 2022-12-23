<?php

namespace CAMOO\Interfaces;

/**
 * Interface FileSystemFactoryInterface
 *
 * @author CamooSarl
 */
interface CacheSystemFactoryInterface
{
    public const CACHE_NAMESPACE = 'core';

    public const CACHE_DIRNAME = 'persistent';

    public const CACHE_TTL = 300;
}
