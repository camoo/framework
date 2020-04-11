<?php
declare(strict_types=1);
namespace CAMOO\Http;

use CAMOO\Exception\Exception;

final class Flash
{
    /** @var \Aura\Session\Segment $oFlashSession */
    private $oFlashSession = null;
    protected static $_create = null;

    private static $_alterTypes = [
        'success',
        'info',
        'warning',
        'error',
        'default',
    ];

    /** @var SessionSegment $session */
    private $session;

    /** @var array $keys */
    private $keys =[];

    public function __construct($oFlashSession = null, ?SessionSegment $session)
    {
        if (null !== $oFlashSession) {
            $this->oFlashSession = $oFlashSession;
        }

        if (!empty($session)) {
            $this->session = $session;
        }
    }

    /**
     * @param string $message
     * @param array $options
     * @return void
     */
    public function set(string $message, array $options) : void
    {
        $default = ['key' => 'flash', 'alert' => 'default'];
        $options += $default;
        $this->keys[$options['key']] = $options['alert'];
        $this->oFlashSession->setFlashNow($options['key'], $message);
        $this->session->write('CAMOO.SYS.FLASH', $this->keys);
    }

    public function __call($name, $xargs)
    {
        if (!in_array($name, array_keys(self::$_alterTypes))) {
            throw new Exception(
                sprintf('Method %s::%s does not exist', get_class($this), $name)
                );
        }

        if (empty($xargs) || count($xargs) > 1 || !preg_match('/\S/', $xargs[0])) {
            throw new Exception(
                sprintf('Parameter is minnsing for %s::%s ', get_class($this), $name)
                );
        }

        $this->set($xargs[0], ['alert' => $name]);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        $this->session->delete('CAMOO.SYS.FLASH.'.$key);
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
