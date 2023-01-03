<?php

declare(strict_types=1);

namespace CAMOO\Utils;

use ArrayAccess;
use ArrayIterator;
use ArrayObject;
use Camoo\Cache\Cache;
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
    private string|int|null $user = null;

    private static ?ServerRequest $withRequest;

    private array $data = [];

    private static ?Cart $created = null;

    private static ?string $basketKey = null;

    /** @int $count */
    private int $count = 0;

    private float $total_price = 0.00;

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
    public function __sleep(): array
    {
        return ['count', 'data', 'total_price', 'user'];
    }

    public function __get(string $key): mixed
    {
        return $this->offsetGet($key);
    }

    public function count(): int
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
            $sObject = Cache::reads(self::$withRequest->getSession()->read('Basket'), '_camoo_hosting_conf');
            self::$created = !($sObject instanceof self) ? new self() : $sObject;
        } elseif (self::$created === null) {
            self::$created = new self();
        }

        return self::$created;
    }

    /** Refresh current Cart Session Identifier */
    public function refresh(bool $force = false): void
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
            Cache::deletes($request->getSession()->read('Basket'), '_camoo_hosting_conf');
        }
        $this->count = 0;
    }

    /** @param int|string|null $uid user ID */
    public function setUserId(int|string|null $uid = null): void
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
            Cache::deletes($request->getSession()->read('Basket'), '_camoo_hosting_conf');
        }

        // REFRESH SESSION
        $this->refresh(true);

        if (null === self::$basketKey) {
            throw new InvalidArgumentException('Cart Key Session cannot be empty');
        }

        return Cache::writes(self::$basketKey, $this, '_camoo_hosting_conf');
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

    public function get(string $key): mixed
    {
        return $this->offsetGet($key);
    }

    /** Adds an Item into Cart */
    public function addItem(string $key, mixed $value): void
    {
        $this->offsetSet($key, $value);
    }

    public function removeItem(string $key): void
    {
        $this->offsetUnset($key);
    }

    public function offsetExists(mixed $key): bool
    {
        return isset($this->data[$key]);
    }

    public function offsetGet(mixed $key): mixed
    {
        if ($this->offsetExists($key)) {
            return $this->data[$key];
        }

        return null;
    }

    public function offsetSet(mixed $key, mixed $value): void
    {
        if (!empty($key)) {
            if (!$this->has($key)) {
                $this->extracted($value);
            }
            $this->data[$key] = $value;
        } else {
            $this->data[] = $value;

            $this->extracted($value);
        }
        $this->save();
    }

    public function offsetUnset(mixed $key): void
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
    protected function getUserId(): int|string|null
    {
        return $this->user;
    }

    private function getRequest(): ?ServerRequest
    {
        return self::$withRequest;
    }

    private function isValueMultiDimensional(array $value): bool
    {
        return count($value) !== count($value, COUNT_RECURSIVE);
    }

    private function extracted(mixed $value): void
    {
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
}
