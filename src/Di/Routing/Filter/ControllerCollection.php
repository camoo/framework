<?php

declare(strict_types=1);

namespace CAMOO\Di\Routing\Filter;

use ArrayIterator;
use IteratorAggregate;

class ControllerCollection implements IteratorAggregate
{
    public function __construct(
        private array $items = []
    ) {
    }

    public function add(string $controller): self
    {
        $this->items[] = $controller;

        return $this;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }
}
