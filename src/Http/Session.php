<?php

declare(strict_types=1);

namespace CAMOO\Http;

use Aura\Session\CsrfToken;
use Aura\Session\Segment;
use Aura\Session\SessionFactory;
use CAMOO\Utils\Configure;

final class Session
{
    public const SEG_NAME = Session::class;

    protected static ?self $instance = null;

    protected static ?array $cookie = null;

    private ?\Aura\Session\Session $oSession = null;

    public function __construct()
    {
        if (null === $this->oSession) {
            $cookies = null !== self::$cookie ? self::$cookie : $_COOKIE;
            $this->oSession = (new SessionFactory())->newInstance($cookies);
            $hCookieParam = Configure::read('Session.cookie');
            $this->oSession->setName(Configure::read('Session.name'));
            $this->oSession->setCookieParams($hCookieParam);
        }
    }

    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->set($key, $value);
    }

    public static function create(?array $cookie = null): Session
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        self::$cookie = $cookie;

        return self::$instance;
    }

    public function segment(?string $sSegment = null): Segment
    {
        $sSegmentName = $sSegment === null ? __NAMESPACE__ : $sSegment;

        return $this->oSession->getSegment($sSegmentName);
    }

    public function destroy(): bool
    {
        return $this->oSession->destroy();
    }

    public function clear(): void
    {
        $this->oSession->clear();
    }

    public function save(): void
    {
        $this->oSession->commit();
    }

    public function set(string $key, mixed $value): void
    {
        $this->segment()->set($key, $value);
    }

    public function get(string $key): mixed
    {
        return $this->segment()->get($key);
    }

    public function getFlash(?string $sSegment = null): Segment
    {
        $sSegmentName = $sSegment === null ? __NAMESPACE__ . '\\Flash' : $sSegment;

        return $this->oSession->getSegment($sSegmentName);
    }

    public function regenerateId(): bool
    {
        return $this->oSession->regenerateId();
    }

    public function getId(): string
    {
        return $this->oSession->getId();
    }

    public function getName(): string
    {
        return $this->oSession->getName();
    }

    public function setName(string $name): string
    {
        return $this->oSession->setName($name);
    }

    /**
     * Sets the session save path.
     *
     * @param string $path The new save path.
     *
     * @see session_save_path()
     */
    public function setSavePath(string $path): string
    {
        return $this->oSession->setSavePath($path);
    }

    /**
     * Gets the session save path.
     *
     * @see session_save_path()
     */
    public function getSavePath(): string
    {
        return $this->oSession->getSavePath();
    }

    /**
     * Returns the CSRF token, creating it if needed (and thereby starting a
     * session).
     */
    public function getCsrfToken(): CsrfToken
    {
        return $this->oSession->getCsrfToken();
    }
}
