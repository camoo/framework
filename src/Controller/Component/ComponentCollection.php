<?php

declare(strict_types=1);

namespace CAMOO\Controller\Component;

use ArrayAccess;
use ArrayIterator;
use ArrayObject;
use CAMOO\Interfaces\ComponentInterface;
use CAMOO\Interfaces\ControllerInterface;
use CAMOO\Utils\Configure;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

/**
 * Class ComponentCollection
 *
 * @author CamooSarl
 */
final class ComponentCollection implements Countable, IteratorAggregate, ArrayAccess
{
    private array $values = [];

    private int $position = 0;

    private ControllerInterface $controller;

    /**
     * This constructor is there in order to be able to create a collection with
     * its values already added
     */
    public function __construct(ControllerInterface $controller)
    {
        $this->controller = &$controller;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator(new ArrayObject($this->values));
    }

    public function add(string $component, array $config = [])
    {
        $namespace = __NAMESPACE__ . '\\';
        $class = sprintf('%s' . $component . '%s', $namespace, 'Component');

        if (!class_exists($class)) {
            $asNameSpace = explode('\\', $namespace);
            array_shift($asNameSpace);
            $nameSpace = '\\' . Configure::read('App.namespace') . '\\' . implode('\\', $asNameSpace);
            $class = sprintf('%s' . $component . '%s', $nameSpace, 'Component');
            if (!class_exists($class)) {
                throw new InvalidArgumentException(sprintf('Class %s not found !', $class));
            }
        }

        $oComponent = new $class($this->controller, $config);

        // Keep compatibility with controllers that declare component properties.
        if (property_exists($this->controller, $component)) {
            $this->controller->{$component} = $oComponent;
        }

        $this->offsetSet($component, $oComponent);
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
        if (!($value instanceof ComponentInterface)) {
            throw new InvalidArgumentException(sprintf('Offset must be an instance of %s', 'ComponentInterface'));
        }

        if (empty($offset)) {
            $this->values[] = $value;
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
