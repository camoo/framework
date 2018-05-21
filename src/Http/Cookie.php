<?php
namespace CAMOO\Http;

use \Aura\Session\SessionFactory;
use CAMOO\Utils\Configure;

class Cookie extends \Overclokk\Cookie\Cookie
{
    protected static $_create = null;

    /**
     * Init class
     *
     * @param array $cookie $_COOKIE global variable.
     */
    public function __construct(array $cookie = array())
    {
        parent::__construct($cookie);
    }

    /**
     * @return \CAMOO\Http\Session
     */
    public static function create()
    {
        if (null === static::$_create) {
            static::$_create = new self;
        }
        return static::$_create;
    }

    public function __get($key)
    {
        return $this->get($key);
    }
    public function __set($name, $xValue)
    {
        $default = array_merge(['name' => $name,'value' => ''], Configure::read('Session.cookie'));
        if (!is_array($xValue)) {
            $default['value'] = $xValue;
        } else {
            $default += $default;
        }
        extract($default);
        return $this->set($name, $value, $expire, $path, $domain, $secure, $httponly);
    }
}
