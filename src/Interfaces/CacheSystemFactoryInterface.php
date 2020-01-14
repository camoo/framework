<?php

namespace CAMOO\Interfaces;

/**
 * Interface FileSystemFactoryInterface
 * @author CamooSarl
 */
interface CacheSystemFactoryInterface
{
    const CACHE_NAMESPACE = 'core';
    const CACHE_DIRNAME = 'persistent';
    const CACHE_TTL = 300;
}
