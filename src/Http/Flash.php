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
    private ?Segment $oFlashSession = null;

    private static array $flashTypes = [
        'success',
        'info',
        'warning',
        'error',
        'default',
    ];

    private SessionSegment $session;

    private array $keys = [];

    public function __construct(?Segment $oFlashSession = null, ?SessionSegment $session = null)
    {
        if (null !== $oFlashSession) {
            $this->oFlashSession = $oFlashSession;
        }

        if (!empty($session)) {
            $this->session = $session;
        }
    }

    public function __call(string $name, mixed $xargs)
    {
        if (!in_array($name, self::$flashTypes, true)) {
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

    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    public function __set(string $key, mixed $value): void
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

    public function get(string $key): mixed
    {
        $this->session->delete('CAMOO.SYS.FLASH.' . $key);

        return $this->oFlashSession->getFlash($key);
    }

    public function setNext(string $key, string $message): void
    {
        $this->oFlashSession->setFlash($key, $message);
    }

    public function getNext(string $key, mixed $alt): mixed
    {
        return $this->oFlashSession->getFlashNext($key, $alt);
    }

    public function keep(): void
    {
        $this->oFlashSession->keepFlash();
    }

    public function destroy(): void
    {
        $this->oFlashSession->clearFlash();
    }
}
