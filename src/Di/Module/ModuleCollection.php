<?php

declare(strict_types=1);

namespace CAMOO\Di\Module;

use ArrayIterator;
use IteratorAggregate;

final class ModuleCollection implements IteratorAggregate
{
    public function __construct(
        private array $items = []
    ) {
    }

    public function add(callable|string $module): self
    {
        $this->items[] = $module;

        return $this;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }
}
