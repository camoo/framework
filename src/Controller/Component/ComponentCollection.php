<?php
declare(strict_types=1);

namespace CAMOO\Controller\Component;

use Countable;
use ArrayAccess;
use InvalidArgumentException;
use CAMOO\Interfaces\ComponentInterface;
use CAMOO\Interfaces\ControllerInterface;
use CAMOO\Utils\Configure;
use IteratorAggregate;
use ArrayIterator;
use Traversable;
use ArrayObject;

/**
 * Class ComponentCollection
 * @author CamooSarl
 */
final class ComponentCollection implements Countable, IteratorAggregate, ArrayAccess
{
    /** @var array */
    private $values = [];

    /** @var int */
    private $position = 0;

    private $controller;

    /**
     * This constructor is there in order to be able to create a collection with
     * its values already added
     */
    public function __construct(ControllerInterface $controller)
    {
        $this->controller =& $controller;
    }

    /**
     * @return Traversable
     */
    public function getIterator() : Traversable
    {
        return new ArrayIterator(new ArrayObject($this->values));
    }

    /**
     * @param string $component
     */
    public function add(string $component, array $config=[])
    {
        $namespace = __NAMESPACE__ .'\\';
        $class = sprintf('%s'.$component.'%s', $namespace, 'Component');

        if (!class_exists($class)) {
            $asNameSpace = explode('\\', $namespace);
            array_shift($asNameSpace);
            $nameSpace = '\\' . Configure::read('App.namespace') .'\\'. implode('\\', $asNameSpace);
            $class = sprintf('%s'.$component.'%s', $nameSpace, 'Component');
            if (!class_exists($class)) {
                throw new InvalidArgumentException(sprintf('Class %s not found !', $class));
            }
        }

        $oComponent = new $class($this->controller, $config);

        /** @var ComponentInterface */
        $this->controller->{$component} = $oComponent;
        $this->offsetSet($component, $oComponent);
    }

    /**
     * Implementation of method declared in \Countable.
     * Provides support for count()
     */
    public function count()
    {
        return count($this->values);
    }

    /**
     * Implementation of method declared in \Iterator
     * Resets the internal cursor to the beginning of the array
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Implementation of method declared in \Iterator
     * Used to get the current key (as for instance in a foreach()-structure
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Implementation of method declared in \Iterator
     * Used to get the value at the current cursor position
     */
    public function current()
    {
        return $this->values[$this->position];
    }

    /**
     * Implementation of method declared in \Iterator
     * Used to move the cursor to the next position
     */
    public function next()
    {
        $this->position++;
    }

    /**
     * Implementation of method declared in \Iterator
     * Checks if the current cursor position is valid
     */
    public function valid()
    {
        return isset($this->values[$this->position]);
    }

    /**
     * Implementation of method declared in \ArrayAccess
     * Used to be able to use functions like isset()
     */
    public function offsetExists($offset)
    {
        return isset($this->values[$offset]);
    }

    /**
     * Implementation of method declared in \ArrayAccess
     * Used for direct access array-like ($collection[$offset]);
     */
    public function offsetGet($offset)
    {
        return $this->values[$offset];
    }

    /**
     * Implementation of method declared in \ArrayAccess
     * Used for direct setting of values
     */
    public function offsetSet($offset=null, $value)
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
    public function offsetUnset($offset)
    {
        unset($this->values[$offset]);
    }
}
