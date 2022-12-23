<?php

declare(strict_types=1);

namespace CAMOO\Event;

/**
 * Interface EventDispatcherInterface
 *
 * @author CamooSarl
 */
interface EventDispatcherInterface
{
    public function dispatchEvent($name, $data = null, $subject = null);
}
