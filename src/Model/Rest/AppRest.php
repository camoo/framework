<?php

declare(strict_types=1);

namespace CAMOO\Model\Rest;

use ArrayAccess;
use ArrayIterator;
use ArrayObject;
use CAMOO\Event\Event;
use CAMOO\Event\EventDispatcherInterface;
use CAMOO\Event\EventDispatcherTrait;
use CAMOO\Event\EventListenerInterface;
use CAMOO\Exception\Exception;
use CAMOO\Interfaces\RestInterface;
use CAMOO\Interfaces\ValidationInterface;
use CAMOO\Utils\Inflector;
use CAMOO\Validation\ValidatorLocatorTrait;
use IteratorAggregate;
use Traversable;

/**
 * Class AppRest
 *
 * @author CamooSarl
 */
abstract class AppRest implements RestInterface, EventListenerInterface, EventDispatcherInterface, IteratorAggregate, ArrayAccess
{
    use ValidatorLocatorTrait;
    use EventDispatcherTrait;

    protected $output;

    /** @var array $errors */
    private $errors = [];

    /** @var bool $valid */
    private $valid = false;

    /** @var array $data */
    private $data = [];

    /** @var array $option */
    private $option = [];

    public function __construct()
    {
        $this->getEventManager()->on($this);
        $this->initialized();
    }

    public function __get(string $key)
    {
        //$caller = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT)[1];
        return $this->offsetGet($key);
    }

    abstract public function validationDefault(ValidationInterface $validator): ValidationInterface;

    /** Initializes Model Rest */
    public function initialized(): void
    {
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator(new ArrayObject($this->data));
    }

    public function has(string $key): bool
    {
        return $this->offsetExists($key);
    }

    public function get(string $key)
    {
        return $this->offsetGet($key);
    }

    /** @param string $value */
    public function set(string $key, $value): void
    {
        $this->offsetSet($key, $value);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    /** @throws Exception */
    public function send(array $callable, bool $argIsHash = true)
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

    public function newRequest(array $data, bool $validate = true, array $options = [])
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
        if ($this->offsetExists($key)) {
            return $this->data[$key];
        }

        return null;
    }

    public function offsetSet($key, $value)
    {
        if ($key) {
            $this->data[$key] = $value;
        } else {
            $this->data[] = $value;
        }
    }

    public function offsetUnset($key)
    {
        unset($this->data[$key]);
    }

    public function implementedEvents(): array
    {
        return [
            'Rest.beforeSend' => 'beforeSend',
            'Rest.afterSend' => 'afterSend',
        ];
    }

    public function beforeSend(Event $event, ArrayObject $option): void
    {
    }

    public function afterSend(Event $event, $response): void
    {
    }

    protected function loadRemoteObject(string $name, object $object): void
    {
        $this->{$name} = $object;
    }
}
