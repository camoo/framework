<?php
namespace CAMOO\Http;

use \Aura\Session\SessionFactory;
use CAMOO\Utils\Configure;

class Session
{
    private $oSession=null;
    protected static $_create = null;
    protected static $__cookie = null;

    public function __construct()
    {
        if (null === $this->oSession) {
            $cookies = null !== static::$__cookie? static::$__cookie : $_COOKIE;
            $this->oSession = (new SessionFactory())->newInstance($cookies);
            $hCookieParam = Configure::read('Session.cookie');
            $hCookieParam['lifetime'] = $hCookieParam['expire'];
            unset($hCookieParam['expire']);
            $this->oSession->setName(Configure::read('Session.name'));
            $this->oSession->setCookieParams($hCookieParam);
        }
    }

    /**
     * @return \CAMOO\Http\Session
     */
    public static function create($cookie = null)
    {
        if (null === static::$_create) {
            static::$_create = new self;
        }
        static::$__cookie = $cookie;
        return static::$_create;
    }

    public function segment($sSegment = null)
    {
        $sSegmentName = $sSegment === null? __NAMESPACE__ : $sSegment;
        return $this->oSession->getSegment($sSegmentName);
    }

    public function destroy()
    {
        return $this->oSession->destroy();
    }


    public function clear()
    {
        return $this->oSession->clear();
    }


    public function save()
    {
        return $this->oSession->commit();
    }


    public function set($key, $value)
    {
        return $this->segment()->set($key, $value);
    }

    public function get($key)
    {
        return $this->segment()->get($key);
    }

    public function getFlash($sSegment = null)
    {
        $sSegmentName = $sSegment === null? __NAMESPACE__. '\\Flash' : $sSegment;
        return $this->oSession->getSegment($sSegmentName);
    }

    public function __get($key)
    {
        return $this->get($key);
    }
    public function __set($key, $value)
    {
        return $this->set($key, $value);
    }

    public function regenerateId()
    {
        return $this->oSession->regenerateId();
    }

    public function getId()
    {
        return $this->oSession->getId();
    }

    public function getName()
    {
        return $this->oSession->getName();
    }

    public function setName($name)
    {
        return $this->oSession->setName($name);
    }

    /**
     *
     * Sets the session save path.
     *
     * @param string $path The new save path.
     *
     * @return string
     *
     * @see session_save_path()
     *
     */
    public function setSavePath($path)
    {
        return $this->oSession->setSavePath($path);
    }

    /**
     *
     * Gets the session save path.
     *
     * @return string
     *
     * @see session_save_path()
     *
     */
    public function getSavePath()
    {
        return $this->oSession->getSavePath($path);
    }


    /**
     *
     * Returns the CSRF token, creating it if needed (and thereby starting a
     * session).
     *
     * @return CsrfToken
     *
     */
    public function getCsrfToken()
    {
        return $this->oSession->getCsrfToken();
    }
}
