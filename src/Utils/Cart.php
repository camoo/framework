<?php
declare(strict_types=1);

namespace CAMOO\Utils;

use CAMOO\Http\ServerRequest;
use CAMOO\Cache\Cache;
use IteratorAggregate;
use ArrayIterator;
use Traversable;
use ArrayAccess;
use Countable;
use ArrayObject;
use InvalidArgumentException;

/**
 * Class Cart
 * @author CamooSarl
 */
class Cart implements IteratorAggregate, ArrayAccess, Countable
{
    /** @var null|int|string $user */
    private $user = null;

    /** @var ServerRequest $withRequest */
    private static $withRequest;

    /** @var array $data */
    private $data = [];

    /** @var null|Cart $created */
    private static $created = null;

    /** @var string $basketKey */
    private static $basketKey = null;

    /** @int $count */
    private $count = 0;

    /** @var float $total_price */
    private $total_price = 0.00;

    public function count()
    {
        return $this->count;
    }

    /**
     * Gets Cart total price
     */
    public function getTotalPrice() : float
    {
        return $this->total_price;
    }

    public static function create(ServerRequest $withRequest)
    {
        self::$withRequest = $withRequest;
        if (!empty(self::$withRequest->getSession()->check('Basket'))) {
            $sObject = Cache::read(self::$withRequest->getSession()->read('Basket'), '_camoo_hosting_conf');
            self::$created = !($sObject instanceof self) ? new self : $sObject;
        } elseif (self::$created === null) {
            self::$created = new self;
        }
        return self::$created;
    }

    /**
     * Refresh current Cart Session Identifier
     *
     * @param bool $force
     * @return void
     */
    public function refresh($force=false) : void
    {
        if ($this->count() > 0) {
            $uid = $this->getUserId();
            $request = $this->getRequest();
            if (!empty($request->getSession()->check('Basket')) || $force === true) {
                $currentIdentifier = $request->getSession()->check('Basket') ? $request->getSession()->read('Basket') : uniqid('Basket', false);
                $asCurrentIdentifier = explode('_', $currentIdentifier);
                if (count($asCurrentIdentifier) < 2 && !empty($uid)) {
                    self::$basketKey = sprintf('Basket_%s', $uid);
                } else {
                    self::$basketKey = $currentIdentifier;
                }
                $request->getSession()->write('Basket', self::$basketKey);
            }
        }
    }

    /**
     * Deletes entire Cart
     */
    public function delete() : void
    {
        $request = $this->getRequest();
        if (!empty($request->getSession()->check('Basket'))) {
            // DELETE PREVIOUS CACHE
            Cache::delete($request->getSession()->read('Basket'), '_camoo_hosting_conf');
        }
        $this->count = 0;
    }

    /**
     * @param null|int|string $uid user ID
     */
    public function setUserId($uid=null) : void
    {
        if (null !== $uid && (!is_numeric($uid) || !is_string($uid))) {
            throw new InvalidArgumentException(sprintf('Value of "uid" should be of type %s or %s', 'Numeric', 'String'));
        }

        $this->user = $uid;
    }

    /**
     * @return null|int|string $uid
     */
    protected function getUserId()
    {
        return $this->user;
    }

    /**
     * Saves current Cart
     */
    public function save() : ?bool
    {
        $request = $this->getRequest();
        if (!empty($request->getSession()->check('Basket'))) {
            // DELETE PREVIOUS CACHE
            Cache::delete($request->getSession()->read('Basket'), '_camoo_hosting_conf');
        }

        // REFRESH SESSION
        $this->refresh(true);

        if (null === self::$basketKey) {
            throw new InvalidArgumentException('Cart Key Session cannot be empty');
        }

        return Cache::write(self::$basketKey, $this, '_camoo_hosting_conf');
    }

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from AdapterFactory::create() instead
     */
    private function __construct()
    {
    }

    /**
     * prevent the instance from being cloned (which would create a second instance of it)
     */
    private function __clone()
    {
    }

    /**
     * Keeps Cart relevant properties during serialization
     */
    public function __sleep()
    {
        return ['count', 'data', 'total_price', 'user'];
    }

    /**
     * @return ServerRequest
     */
    private function getRequest() : ServerRequest
    {
        return self::$withRequest;
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
     * Adds an Item into Cart
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function addItem(string $key, $value) : void
    {
        $this->offsetSet($key, $value);
    }

    /**
     * Removes an Item from Cart
     *
     * @return void
     */
    public function removeItem(string $key) : void
    {
        $this->offsetUnset($key);
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
        if (!empty($key)) {
            if (!$this->has($key)) {
                if (!empty($value)) {
                    if (is_array($value) && $this->isValueMultiDimensional($value)) {
                        foreach ($value as $hVal) {
                            if (array_key_exists('price', $hVal)) {
                                $this->total_price += (float) $hVal['price'];
                            }

                            ++$this->count;
                        }
                    } else {
                        ++$this->count;
                        if (array_key_exists('price', $value)) {
                            $this->total_price += (float) $value['price'];
                        }
                    }
                }
            }
            $this->data[$key] = $value;
        } else {
            $this->data[] = $value;

            if (!empty($value)) {
                if (is_array($value) && $this->isValueMultiDimensional($value)) {
                    foreach ($value as $hVal) {
                        if (array_key_exists('price', $hVal)) {
                            $this->total_price += (float) $hVal['price'];
                        }

                        ++$this->count;
                    }
                } else {
                    ++$this->count;
                    if (array_key_exists('price', $value)) {
                        $this->total_price += (float) $value['price'];
                    }
                }
            }
        }
        $this->save();
    }

    public function offsetUnset($key)
    {
        if ($this->has($key)) {
            $value = $this->data[$key];
            unset($this->data[$key]);
            if (!empty($value)) {
                if (is_array($value) && $this->isValueMultiDimensional($value)) {
                    foreach ($value as $hVal) {
                        if (array_key_exists('price', $hVal)) {
                            $this->total_price -= (float) $hVal['price'];
                        }

                        --$this->count;
                    }
                } else {
                    --$this->count;
                    if (array_key_exists('price', $value)) {
                        $this->total_price -= (float) $value['price'];
                    }
                }
            }
            $this->save();
        }
    }

    private function isValueMultiDimensional(array $value) : bool
    {
        return count($value) !== count($value, COUNT_RECURSIVE);
    }
}
