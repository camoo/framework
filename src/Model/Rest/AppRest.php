<?php
declare(strict_types=1);

namespace CAMOO\Model\Rest;

use CAMOO\Validation\ValidatorLocatorTrait;
use CAMOO\Event\EventDispatcherInterface;
use CAMOO\Event\EventDispatcherTrait;
use CAMOO\Event\EventListenerInterface;
use CAMOO\Event\Event;
use ArrayObject;
use CAMOO\Exception\Exception;
use CAMOO\Interfaces\ValidationInterface;
use CAMOO\Interfaces\RestInterface;
use CAMOO\Utils\Inflector;
use IteratorAggregate;
use ArrayIterator;
use Traversable;
use ArrayAccess;

/**
 * Class AppRest
 * @author CamooSarl
 */
abstract class AppRest implements RestInterface, EventListenerInterface, EventDispatcherInterface, IteratorAggregate, ArrayAccess
{
    use ValidatorLocatorTrait;
    use EventDispatcherTrait;

    /** @var array $errors */
    private $errors = [];

    /** @var bool $valid */
    private $valid = false;

    /** @var array $data */
    private $data = [];

    /** @var array $option */
    private $option = [];

    /**@var mixed $output */
    protected $output;

    abstract public function validationDefault(ValidationInterface $validator) : ValidationInterface;

    public function __construct()
    {
        $this->getEventManager()->on($this);
        $this->initialized();
    }

    /**
     * Initializes Model Rest
     *
     * @return void
     */
    public function initialized() : void
    {
    }

    /**
     * @param string $name
     * @param object $object
     * @return void
     */
    protected function loadRemoteObject(string $name, object $object) : void
    {
        $this->{$name} = $object;
    }

    /**
     * @return Traversable
     */
    public function getIterator() : Traversable
    {
        return new ArrayIterator(new ArrayObject($this->data));
    }

    public function __get(string $key)
    {
        //$caller = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT)[1];
        return $this->offsetGet($key);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key) : bool
    {
        return $this->offsetExists($key);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        return $this->offsetGet($key);
    }

    /**
     * @param string $key
     * @param string $value
     * @return void
     */
    public function set(string $key, $value) : void
    {
        $this->offsetSet($key, $value);
    }

    public function getErrors()
    {
        $this->errors;
    }

    /**
     * @param array $callable
     * @param bool $argIsHash
     * @throws Exception
     * @return mixed
     */
    public function send(array $callable, bool $argIsHash=true)
    {
        if (!$this->valid) {
            throw new Exception('Invalid Data Provided !');
        }

        $event = $this->dispatchEvent('Rest.beforeSend', ['data' => new ArrayObject($this->option)]);
        $object = $event->getSubject();
        $args = $argIsHash === true ? [$object->data] : $object->data;

        if (is_array($callable) && !empty($callable) && preg_match('/^::/', $callable[0])) {
            $remoteObject = str_replace('::', '', $callable[0]);
            $callable[0] = $this->{$remoteObject};
        }
        $this->output = call_user_func_array($callable, $args);
        $this->dispatchEvent('Rest.afterSend', ['data' => $this->output]);

        return $this->output;
    }

    public function newRequest(array $data, bool $validate=true, array $options=[])
    {
        $default = ['validation' => 'default'];
        $options += $default;
        if ($validate === true) {
            $suffix = Inflector::classify($options['validation']);
            $validationMethod = 'Validation' . $suffix;
            if (empty($options['validation']) || !method_exists($this, $validationMethod)) {
                throw new Exception(sprintf('Validation method %s not found in %s', $validationMethod, get_class($this)));
            }
            $validator = $this->{$validationMethod}($this->getValidatorLocator()->get());
            $this->valid = $validator->isValid($data);
            if (!$this->valid) {
                $this->errors = $validator->getErrors();
            } else {
                $this->data = $data;
                unset($options['validation']);
                $this->option = $options;
            }
        } else {
            $this->valid = true;
            $this->data = $data;
        }
        return $this;
    }

    public function offsetExists($key)
    {
        return isset($this->data[$key]);
    }

    public function offsetGet($key)
    {
        if ($this->offsetExists($key)):
            return $this->data[$key]; else:
        return null;
        endif;
    }

    public function offsetSet($key, $value)
    {
        if ($key):
            $this->data[$key] = $value; else:
        $this->data[] = $value;
        endif;
    }

    public function offsetUnset($key)
    {
        unset($this->data[$key]);
    }

    public function implementedEvents() : array
    {
        return [
            'Rest.beforeSend' => 'beforeSend',
            'Rest.afterSend' => 'afterSend',
        ];
    }

    /**
     * @param Event $event
     * @return void
     */
    public function beforeSend(Event $event, \ArrayObject $option) : void
    {
    }

    /**
     * @param Event $event
     * @param mixed $response
     * @return void
     */
    public function afterSend(Event $event, $response) : void
    {
    }
}
