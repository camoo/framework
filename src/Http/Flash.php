<?php

declare(strict_types=1);

namespace CAMOO\Http;

use Aura\Session\Segment;
use CAMOO\Exception\Exception;

/**
 * @method void success(string $message)
 * @method void info(string $message)
 * @method void warning(string $message)
 * @method void error(string $message)
 * @method void default(string $message)
 */
final class Flash
{
    /** @var Segment $oFlashSession */
    private $oFlashSession = null;

    /** @var string[] $flashTypes */
    private static $flashTypes = [
        'success',
        'info',
        'warning',
        'error',
        'default',
    ];

    /** @var SessionSegment $session */
    private $session;

    /** @var array $keys */
    private $keys = [];

    public function __construct($oFlashSession = null, ?SessionSegment $session = null)
    {
        if (null !== $oFlashSession) {
            $this->oFlashSession = $oFlashSession;
        }

        if (!empty($session)) {
            $this->session = $session;
        }
    }

    public function __call($name, $xargs)
    {
        if (!in_array($name, array_keys(self::$flashTypes))) {
            throw new Exception(
                sprintf('Method %s::%s does not exist', get_class($this), $name)
            );
        }

        if (empty($xargs) || count($xargs) > 1 || !preg_match('/\S/', $xargs[0])) {
            throw new Exception(
                sprintf('Parameter is missing for %s::%s ', get_class($this), $name)
            );
        }

        $this->set($xargs[0], ['alert' => $name]);
    }

    public function __get($key)
    {
        return $this->get($key);
    }

    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    public function set(string $message, array $options): void
    {
        $default = ['key' => 'flash', 'alert' => 'default'];
        $options += $default;
        $this->keys[$options['key']] = $options['alert'];
        $this->oFlashSession->setFlashNow($options['key'], $message);
        $this->session->write('CAMOO.SYS.FLASH', $this->keys);
    }

    public function get(string $key)
    {
        $this->session->delete('CAMOO.SYS.FLASH.' . $key);

        return $this->oFlashSession->getFlash($key);
    }

    public function setNext($key, $message)
    {
        $this->oFlashSession->setFlash($key, $message);
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
