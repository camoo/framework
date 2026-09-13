<?php

declare(strict_types=1);

namespace CAMOO\Http;

use Aura\Session\CsrfToken;
use Aura\Session\Segment;
use Aura\Session\SessionFactory;
use CAMOO\Utils\Configure;

final class Session
{
    public const string SEG_NAME = Session::class;

    protected static ?self $instance = null;

    protected static ?array $cookie = null;

    private ?\Aura\Session\Session $oSession = null;

    public function __construct()
    {
        if (null === $this->oSession) {
            $cookies = self::$cookie ?? $_COOKIE;
            $this->oSession = new SessionFactory()->newInstance($cookies);
            $hCookieParam = Configure::read('Session.cookie');
            if (!is_array($hCookieParam)) {
                $hCookieParam = [];
            }
            $isHttps = isset($_SERVER['HTTPS'])
                && strtolower((string)$_SERVER['HTTPS']) !== ''
                && strtolower((string)$_SERVER['HTTPS']) !== 'off';
            $trustedProxies = Configure::read('App.trusted_proxies');
            if (!$isHttps && is_array($trustedProxies)) {
                $remoteAddress = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
                $forwardedProto = strtolower(trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
                $isHttps = $forwardedProto === 'https' && in_array($remoteAddress, $trustedProxies, true);
            }
            $sameSite = $hCookieParam['samesite'] ?? 'Lax';
            if (!is_string($sameSite) || !in_array(strtolower($sameSite), ['lax', 'strict', 'none'], true)) {
                $sameSite = 'Lax';
            }
            ini_set('session.cookie_samesite', $sameSite);
            $hCookieParam += [
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => $sameSite,
            ];
            $sessionName = Configure::read('Session.name') ?? 'CAMOOSESS';
            $this->oSession->setName($sessionName);
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
        self::$instance ??= new self();
        self::$cookie = $cookie;

        return self::$instance;
    }

    public function segment(?string $sSegment = null): Segment
    {
        $sSegmentName = $sSegment ?? __NAMESPACE__;

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
        $sSegmentName = $sSegment ?? __NAMESPACE__ . '\\Flash';

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

    /** @return array<string, mixed> */
    public function getCookieParams(): array
    {
        return $this->oSession->getCookieParams();
    }

    public function getName(): string
    {
        return $this->oSession->getName();
    }

    public function setName(string $name): void
    {
        $this->oSession->setName($name);
    }

    /**
     * Sets the session save path.
     *
     * @param string $path The new save path.
     *
     * @see session_save_path()
     */
    public function setSavePath(string $path): string|false
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
