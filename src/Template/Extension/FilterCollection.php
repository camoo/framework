<?php

declare(strict_types=1);

namespace CAMOO\Template\Extension;

use ArrayAccess;
use CAMOO\Interfaces\TemplateFilterInterface;
use Countable;
use InvalidArgumentException;
use Iterator;

/**
 * Class FilterCollection
 *
 * @author CamooSarl
 */
final class FilterCollection implements Countable, Iterator, ArrayAccess
{
    private array $values = [];

    private int $position = 0;

    /**
     * This constructor is there in order to be able to create a collection with
     * its values already added
     */
    public function __construct(array $values = [])
    {
        foreach ($values as $value) {
            $this->offsetSet(null, $value);
        }
    }

    public function add($filter)
    {
        $this->offsetSet(null, $filter);
    }

    /**
     * Implementation of method declared in \Countable.
     * Provides support for count()
     */
    public function count(): int
    {
        return count($this->values);
    }

    /**
     * Implementation of method declared in \Iterator
     * Resets the internal cursor to the beginning of the array
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * Implementation of method declared in \Iterator
     * Used to get the current key (as for instance in a foreach()-structure
     */
    public function key(): mixed
    {
        return $this->position;
    }

    /**
     * Implementation of method declared in \Iterator
     * Used to get the value at the current cursor position
     */
    public function current(): mixed
    {
        return $this->values[$this->position];
    }

    /**
     * Implementation of method declared in \Iterator
     * Used to move the cursor to the next position
     */
    public function next(): void
    {
        $this->position++;
    }

    /**
     * Implementation of method declared in \Iterator
     * Checks if the current cursor position is valid
     */
    public function valid(): bool
    {
        return isset($this->values[$this->position]);
    }

    /**
     * Implementation of method declared in \ArrayAccess
     * Used to be able to use functions like isset()
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->values[$offset]);
    }

    /**
     * Implementation of method declared in \ArrayAccess
     * Used for direct access array-like ($collection[$offset]);
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->values[$offset];
    }

    /**
     * Implementation of method declared in \ArrayAccess
     * Used for direct setting of values
     *
     * @param mixed|null $offset
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!($value instanceof TemplateFilterInterface)) {
            throw new InvalidArgumentException(sprintf('Offset must be an instance of %s', 'TemplateFilterInterface'));
        }

        if (empty($offset)) {
            $asFilters = $value->getFilters();
            foreach ($asFilters as $sFilter) {
                $this->values[] = $sFilter;
            }
        } else {
            $this->values[$offset] = $value;
        }
    }

    /**
     * Implementation of method declared in \ArrayAccess
     * Used for unset()
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->values[$offset]);
    }
}
