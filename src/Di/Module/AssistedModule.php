<?php

namespace CAMOO\Di\Module;

use CAMOO\Di\Annotation\Assisted;
use CAMOO\Di\Interceptor\AssistedInterceptor;
use Ray\Di\AbstractModule;

class AssistedModule extends AbstractModule
{
    protected function configure()
    {
        // @Assisted
        $this->bindInterceptor(
            $this->matcher->any(),
            $this->matcher->annotatedWith(Assisted::class),
            [AssistedInterceptor::class]
        );
    }
}
