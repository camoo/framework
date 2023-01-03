<?php

declare(strict_types=1);

namespace CAMOO\Di\Cache;

use Camoo\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;

/**
 * Exposes methods from Camoo Cache class as a cache engine for
 * Doctrine.
 */
class CacheAdapter extends CacheProvider
{
    /** The Cache config name to use. */
    protected string $config;

    /**
     * Constructor.
     *
     * @param string $configName The Cache config name to use.
     */
    public function __construct(string $configName)
    {
        $this->config = $configName;
    }

    /** {@inheritDoc} */
    protected function doFetch($id)
    {
        return Cache::reads($id, $this->config);
    }

    /** {@inheritDoc} */
    protected function doContains($id)
    {
        return Cache::reads($id, $this->config) !== false;
    }

    /** {@inheritDoc} */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        return Cache::writes($id, $data, $this->config);
    }

    /** {@inheritDoc} */
    protected function doDelete($id)
    {
        return Cache::deletes($id, $this->config);
    }

    /** {@inheritDoc} */
    protected function doFlush()
    {
        return Cache::clears($this->config);
    }

    /** {@inheritDoc} */
    protected function doGetStats()
    {
        return null;
    }
}
