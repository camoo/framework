<?php

declare(strict_types=1);

namespace CAMOO\Http;

use Aura\Session\Segment;
use CAMOO\Exception\Exception;
use CAMOO\Exception\Http\ForbiddenException;
use CAMOO\Exception\Http\MethodNotAllowedException;
use CAMOO\Utils\QueryData;
use CAMOO\Utils\Security;
use GuzzleHttp\Psr7\ServerRequest as BaseServerRequest;

class ServerRequest
{
    /** @var array */
    private const REQUEST_METHODS = [
        'POST',
        'GET',
        'PUT',
        'DELETE',
        'PATCH',
    ];

    public array $query = [];

    public array $data = [];

    public ?Cookie $cookie;

    /** @var bool $isProxy defines if your app is running under a proxy server */
    public bool $isProxy = false;

    public ?Flash $Flash = null;

    private ?BaseServerRequest $oRequest;

    private Segment $session;

    private array $__session = [Session::class, 'create'];

    private array $__cookie = [Cookie::class, 'create'];

    private array $queryDataMaps = [
        'query' => 'getQueryParams',
        'data' => 'getParsedBody',
    ];

    public function __construct(?BaseServerRequest $oRequest = null)
    {
        $this->oRequest = $oRequest;
        $this->invoker();
    }

    public function __call(string $name, array $xargs): mixed
    {
        if (mb_substr($name, 0, 3) === 'get' && in_array(
            mb_strtolower(mb_substr($name, 3)),
            array_keys($this->queryDataMaps)
        )) {
            $xData = $this->__queryData($this->oRequest->{$this->queryDataMaps[mb_strtolower(mb_substr($name, 3))]}());
            $xData = $this->satanise($xData);

            return empty($xargs) ? $xData : (new QueryData($xData))->get($xargs[0]);
        } elseif (in_array($name, array_keys($this->queryDataMaps))) {
            if (empty($xargs) || count($xargs) > 1 || !preg_match('/\S/', $xargs[0])) {
                throw new Exception(
                    sprintf('Method %s::%s does not exist', get_class($this), $name)
                );
            }
            $xData = $this->__queryData($this->oRequest->{$this->queryDataMaps[$name]}(), false)->get($xargs[0]);

            return $this->satanise($xData);
        }
        throw new Exception(
            sprintf('Method %s::%s does not exist', get_class($this), $name)
        );
    }

    private function __getSession(): Session
    {
        return call_user_func($this->__session);
    }

    private function __getFlash(?Segment $oFlashSession, ?SessionSegment $sessionSegment): Flash
    {
        return new Flash($oFlashSession, $sessionSegment);
    }

    private function __getCookie(): Cookie
    {
        return call_user_func($this->__cookie);
    }

    private function __queryData(mixed $xData, bool $bAll = true): QueryData|array|null
    {
        $oxData = new QueryData($xData);

        return $bAll === true ? $oxData->all() : $oxData;
    }

    public function getCookieParams(): array
    {
        return $this->oRequest->getCookieParams();
    }

    public function getAttribute(string $key): mixed
    {
        return $this->oRequest->getAttribute($key);
    }

    public function getData(?string $key = null): mixed
    {
        return $this->getRequestData('data', $key);
    }

    public function getQuery(?string $key = null): mixed
    {
        return $this->getRequestData('query', $key);
    }

    public function getRemoteIp(): string
    {
        if ($this->isProxy && $this->getEnv('HTTP_X_FORWARDED_FOR')) {
            $addresses = explode(',', $this->getEnv('HTTP_X_FORWARDED_FOR'));
            $clientIp = end($addresses);
        } elseif ($this->isProxy && $this->getEnv('HTTP_CLIENT_IP')) {
            $clientIp = $this->getEnv('HTTP_CLIENT_IP');
        } else {
            $clientIp = $this->getEnv('REMOTE_ADDR');
        }

        return trim($clientIp);
    }

    /**
     * @throw Exception
     * @throw ForbiddenException
     */
    public function is(string $request_method): bool
    {
        if (strtolower($request_method) === 'ajax') {
            $checkAjax = null !== $this->getEnv('HTTP_X_REQUESTED_WITH') &&
                $this->getEnv('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
            // CHECK TO ENSURE REFERRER URL IS ON THIS DOMAIN
            if ($checkAjax === true && !str_contains($this->getEnv('HTTP_REFERER'), $this->getEnv('HTTP_HOST'))) {
                throw new ForbiddenException('Ajax:: Bad Referrer !');
            }

            return $checkAjax;
        }

        if (!in_array(strtoupper($request_method), static::REQUEST_METHODS)) {
            throw new Exception(sprintf('%s is not an allowed request method', $request_method));
        }

        return strtoupper($this->oRequest->getMethod()) === strtoupper($request_method);
    }

    public function getSession(): SessionSegment
    {
        return new SessionSegment($this->session);
    }

    /** @throw HttpExceptionInterface */
    public function allowMethod(array $asMethod = []): void
    {
        if (empty($asMethod)) {
            throw new Exception('Allowed method is not defined !');
        }

        if (!in_array(strtolower($this->oRequest->getMethod()), array_map('strtolower', $asMethod))) {
            throw new MethodNotAllowedException();
        }
    }

    public function getMethod(): string
    {
        return $this->oRequest->getMethod();
    }

    public function getReferer(): ?string
    {
        return $this->getEnv('HTTP_REFERER');
    }

    /** @return mixed|null */
    public function getEnv(string $param): mixed
    {
        $serverParams = $this->oRequest->getServerParams();

        return array_key_exists($param, $serverParams) ? $serverParams[$param] : null;
    }

    public function getRequestTarget(): string
    {
        return $this->oRequest->getRequestTarget();
    }

    private function getRequestData(string $type, ?string $key = null): mixed
    {
        if ($key === null) {
            $xData = $this->__queryData($this->oRequest->{$this->queryDataMaps[$type]}());
        } else {
            $xData = $this->__queryData($this->oRequest->{$this->queryDataMaps[$type]}(), false)->get($key);
        }

        return $this->satanise($xData);
    }

    private function invoker(): void
    {
        $oSession = $this->__getSession();

        $this->session = $oSession->segment(Session::SEG_NAME);

        if (!empty($this->oRequest)) {
            $this->query = $this->satanise($this->__queryData($this->oRequest->getQueryParams()));
            $this->data = $this->satanise($this->__queryData($this->oRequest->getParsedBody()));
        }

        $this->cookie = $this->__getCookie();
        $this->Flash = $this->__getFlash($oSession->getFlash(), $this->getSession());
    }

    private function satanise(mixed $xData): mixed
    {
        if (is_numeric($xData)) {
            return $xData;
        }
        if (is_object($xData)) {
            throw new Exception('Invalid Data type! Only string|Array are allowed');
        }
        if (is_array($xData)) {
            if (array_key_exists('__csrf_Token', $xData)) {
                unset($xData['__csrf_Token']);
            }
            if (count($xData) === 0) {
                return $xData;
            }

            return array_map(
                fn (mixed $data) => is_array($data) ? $this->satanise($data) : Security::satanizer($data),
                $xData
            );
        }

        return Security::satanizer($xData);
    }
}
