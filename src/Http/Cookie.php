<?php

declare(strict_types=1);

namespace CAMOO\Http;

use CAMOO\Utils\Configure;
use Overclokk\Cookie\Cookie as BaseCookie;

class Cookie extends BaseCookie
{
    protected static ?self $createInstance = null;

    /**
     * Init class
     *
     * @param array $cookie $_COOKIE global variable.
     */
    public function __construct(array $cookie = [])
    {
        parent::__construct($cookie);
    }

    public function __get(string $key): ?string
    {
        return $this->get($key);
    }

    public function __set(string $name, mixed $xValue)
    {
        $default = array_merge(['name' => $name, 'value' => ''], Configure::read('Session.cookie'));
        if (!is_array($xValue)) {
            $default['value'] = $xValue;
        } else {
            $default += $default;
        }

        return $this->set(
            $name,
            $default['value'],
            $default['expire'],
            $default['path'],
            $default['domain'],
            $default['secure'],
            $default['httponly']
        );
    }

    public static function create(): ?self
    {
        if (null === self::$createInstance) {
            self::$createInstance = new self();
        }

        return self::$createInstance;
    }
}
