<?php

declare(strict_types=1);

namespace CAMOO\Utils;

use ArrayAccess;
use ArrayIterator;
use ArrayObject;
use CAMOO\Cache\Cache;
use CAMOO\Http\ServerRequest;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

/**
 * Class Cart
 *
 * @author CamooSarl
 */
class Cart implements IteratorAggregate, ArrayAccess, Countable
{
    /** @var int|string|null $user */
    private $user = null;

    /** @var ServerRequest $withRequest */
    private static $withRequest;

    /** @var array $data */
    private $data = [];

    /** @var Cart|null $created */
    private static $created = null;

    /** @var string $basketKey */
    private static $basketKey = null;

    /** @int $count */
    private $count = 0;

    /** @var float $total_price */
    private $total_price = 0.00;

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from AdapterFactory::create() instead
     */
    private function __construct()
    {
    }

    /** prevent the instance from being cloned (which would create a second instance of it) */
    private function __clone()
    {
    }

    /** Keeps Cart relevant properties during serialization */
    public function __sleep()
    {
        return ['count', 'data', 'total_price', 'user'];
    }

    public function __get(string $key)
    {
        return $this->offsetGet($key);
    }

    public function count()
    {
        return $this->count;
    }

    /** Gets Cart total price */
    public function getTotalPrice(): float
    {
        return $this->total_price;
    }

    public static function create(ServerRequest $withRequest): ?Cart
    {
        self::$withRequest = $withRequest;
        if (!empty(self::$withRequest->getSession()->check('Basket'))) {
            $sObject = Cache::read(self::$withRequest->getSession()->read('Basket'), '_camoo_hosting_conf');
            self::$created = !($sObject instanceof self) ? new self() : $sObject;
        } elseif (self::$created === null) {
            self::$created = new self();
        }

        return self::$created;
    }

    /**
     * Refresh current Cart Session Identifier
     *
     * @param bool $force
     */
    public function refresh($force = false): void
    {
        if ($this->count() > 0) {
            $uid = $this->getUserId();
            $request = $this->getRequest();
            if (!empty($request->getSession()->check('Basket')) || $force === true) {
                $currentIdentifier = $request->getSession()->check('Basket') ?
                    $request->getSession()->read('Basket') : uniqid('Basket', false);
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

    /** Deletes entire Cart */
    public function delete(): void
    {
        $request = $this->getRequest();
        if (!empty($request->getSession()->check('Basket'))) {
            // DELETE PREVIOUS CACHE
            Cache::delete($request->getSession()->read('Basket'), '_camoo_hosting_conf');
        }
        $this->count = 0;
    }

    /** @param int|string|null $uid user ID */
    public function setUserId($uid = null): void
    {
        if (null !== $uid && !in_array(gettype($uid), ['string', 'integer'])) {
            throw new InvalidArgumentException(sprintf(
                'Value of "uid" should be of type %s or %s',
                'Numeric',
                'String'
            ));
        }

        $this->user = $uid;
    }

    /** Saves current Cart */
    public function save(): ?bool
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

    public function addRequest(ServerRequest $request): void
    {
        self::$withRequest = $request;
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

    /**
     * Adds an Item into Cart
     */
    public function addItem(string $key, $value): void
    {
        $this->offsetSet($key, $value);
    }

    public function removeItem(string $key): void
    {
        $this->offsetUnset($key);
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
        if (!empty($key)) {
            if (!$this->has($key)) {
                if (!empty($value)) {
                    if (is_array($value) && $this->isValueMultiDimensional($value)) {
                        foreach ($value as $hVal) {
                            if (array_key_exists('price', $hVal)) {
                                $this->total_price += (float)$hVal['price'];
                            }

                            ++$this->count;
                        }
                    } else {
                        ++$this->count;
                        if (array_key_exists('price', $value)) {
                            $this->total_price += (float)$value['price'];
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
                            $this->total_price += (float)$hVal['price'];
                        }

                        ++$this->count;
                    }
                } else {
                    ++$this->count;
                    if (array_key_exists('price', $value)) {
                        $this->total_price += (float)$value['price'];
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
                            $this->total_price -= (float)$hVal['price'];
                        }

                        --$this->count;
                    }
                } else {
                    --$this->count;
                    if (array_key_exists('price', $value)) {
                        $this->total_price -= (float)$value['price'];
                    }
                }
            }
            $this->save();
        }
    }

    /** @return int|string|null $uid */
    protected function getUserId()
    {
        return $this->user;
    }

    private function getRequest(): ServerRequest
    {
        return self::$withRequest;
    }

    private function isValueMultiDimensional(array $value): bool
    {
        return count($value) !== count($value, COUNT_RECURSIVE);
    }
}
