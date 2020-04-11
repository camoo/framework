<?php
declare(strict_types=1);
namespace CAMOO\Http;

final class Flash
{
    private $oFlashSession = null;
    protected static $_create = null;
    private static $_flashSession = null;

    public function __construct($oFlashSession = null)
    {
        if (null !== $oFlashSession) {
            $this->oFlashSession = $oFlashSession;
        }
    }

    /**
     * @return \CAMOO\Http\Flash
     */
    public static function create($oFlash)
    {
        if (null === static::$_create) {
            static::$_create = new self;
        }
        static::$_flashSession = $oFlash;
        return static::$_create;
    }

    /**
     * @return \CAMOO\Http\Flash
     */
    public function initialize()
    {
        if (null === $this->oFlashSession) {
            $this->oFlashSession = self::$_flashSession;
        }
        return $this;
    }

    public function set($key, $message)
    {
        return $this->oFlashSession->setFlashNow($key, $message);
    }

    public function get($key)
    {
        return $this->oFlashSession->getFlash($key);
    }

    public function __get($key)
    {
        return $this->get($key);
    }

    public function __set($key, $value)
    {
        return $this->set($key, $value);
    }

    public function setNext($key, $message)
    {
        return $this->oFlashSession->setFlash($key, $message);
    }

    public function getNext($key, $alt)
    {
        return $this->oFlashSession->getFlashNext($key, $alt);
    }

    public function keep()
    {
        return $this->oFlashSession->keepFlash();
    }

    public function destroy()
    {
        return $this->oFlashSession->clearFlash();
    }
}
