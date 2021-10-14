<?php
declare(strict_types=1);

namespace CAMOO\Http;

use CAMOO\Utils\QueryData;
use CAMOO\Exception\Exception;
use CAMOO\Utils\Security;
use CAMOO\Exception\Http\MethodNotAllowedException;
use GuzzleHttp\Psr7\ServerRequest as BaseServerRequest;
use CAMOO\Exception\Http\ForbiddenException;

class ServerRequest
{

    /** @var array */
    private const REQUEST_METHODS = [
        'POST',
        'GET',
        'PUT',
        'DELETE',
        'PATCH'
    ];

    /** @var BaseServerRequest $oRequest */
    private $oRequest;

    /** @var array $query */
    public $query = [];

    /** @var array $data */
    public $data = [];

    /** @var array $cookie */
    public $cookie = [];

    private $session;

    /** @var bool $isProxy defines if your app is running under a proxy server */
    public $isProxy = false;

    /** @var Flash $Flash */
    public $Flash = null;

    /** @var array $__session */
    private $__session = [Session::class, 'create'];

    /** @var array $__cookie */
    private $__cookie = [Cookie::class, 'create'];

    /** @var array $_queryDataMaps */
    private $_queryDataMaps = [
        'query' => 'getQueryParams',
        'data'  => 'getParsedBody',
    ];

    public function __construct(?BaseServerRequest $oRequest = null)
    {
        $this->oRequest = $oRequest;
        $this->invoker();
    }

    public function getCookieParams() : array
    {
        return $this->oRequest->getCookieParams();
    }

    public function getAttribute(string $key)
    {
        return $this->oRequest->getAttribute($key);
    }

    public function __call($name, $xargs)
    {
        if (mb_substr($name, 0, 3) === 'get' && in_array(mb_strtolower(mb_substr($name, 3)), array_keys($this->_queryDataMaps))) {
            $xData = $this->__queryData($this->oRequest->{$this->_queryDataMaps[mb_strtolower(mb_substr($name, 3))]}());
            $xData = $this->_satanize($xData);
            return empty($xargs)? $xData : (new QueryData($xData))->get($xargs[0]);
        } elseif (in_array($name, array_keys($this->_queryDataMaps))) {
            if (empty($xargs) || count($xargs) > 1 || !preg_match('/\S/', $xargs[0])) {
                throw new Exception(
                    sprintf('Method %s::%s does not exist', get_class($this), $name)
                );
            }
            $xData = $this->__queryData($this->oRequest->{$this->_queryDataMaps[$name]}(), false)->get($xargs[0]);
            return $this->_satanize($xData);
        }
        throw new Exception(
            sprintf('Method %s::%s does not exist', get_class($this), $name)
        );
    }

    /**
     * @param string $type
     * @param null|string $key
     * @return mixed
     */
    private function getRequestData(string $type, ?string $key=null)
    {
        if ($key === null) {
            $xData = $this->__queryData($this->oRequest->{$this->_queryDataMaps[$type]}());
        } else {
            $xData = $this->__queryData($this->oRequest->{$this->_queryDataMaps[$type]}(), false)->get($key);
        }
        return $this->_satanize($xData);
    }

    /**
     * @param null|string $key
     * @return mixed
     */
    public function getData(?string $key=null)
    {
        return $this->getRequestData('data', $key);
    }

    /**
     * @param null|string $key
     * @return mixed
     */
    public function getQuery(?string $key=null)
    {
        return $this->getRequestData('query', $key);
    }

    /**
     * @return string
     */
    public function getRemoteIp() : string
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

    private function __getSession()
    {
        return call_user_func($this->__session);
    }

    private function __getFlash($oFlashSession, $sessionSegment) : Flash
    {
        return new Flash($oFlashSession, $sessionSegment);
    }

    private function __getCookie()
    {
        return call_user_func($this->__cookie);
    }

    private function __queryData($xData, $bAll = true)
    {
        $oxData = new QueryData($xData);
        return $bAll === true? $oxData->all() : $oxData;
    }

    private function invoker()
    {
        $oSession = $this->__getSession();

        /** @var SessionSegment */
        $this->session = $oSession->segment(Session::SEG_NAME);

        if (!empty($this->oRequest)) {
            $this->query = $this->_satanize($this->__queryData($this->oRequest->getQueryParams()));
            $this->data = $this->_satanize($this->__queryData($this->oRequest->getParsedBody()));
        }

        $this->cookie = $this->__getCookie();
        $this->Flash = $this->__getFlash($oSession->getFlash(), $this->getSession());
    }

    /**
     * @param string $request_method
     * @throw Exception
     * @throw ForbiddenException
     * @return bool
     */
    public function is(string $request_method) : bool
    {
        if (strtolower($request_method) === 'ajax') {
            $checkAjax = null !== $this->getEnv('HTTP_X_REQUESTED_WITH') && $this->getEnv('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
            // CHECK TO ENSURE REFERRER URL IS ON THIS DOMAIN
            if ($checkAjax === true && strpos($this->getEnv('HTTP_REFERER'), $this->getEnv('HTTP_HOST')) === false) {
                throw new ForbiddenException('Ajax:: Bad Referrer !');
            }

            return $checkAjax;
        }

        if (!in_array(strtoupper($request_method), static::REQUEST_METHODS)) {
            throw new Exception(sprintf('%s is not an allowed request method', $request_method));
        }

        return strtoupper($this->oRequest->getMethod()) === strtoupper($request_method);
    }

    /**
     * @return SessionSegment
     */
    public function getSession() : SessionSegment
    {
        return new SessionSegment($this->session);
    }

    /**
     * @param string|array $xData
     */
    private function _satanize($xData)
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
            return array_map(function ($data) {
                if (!is_array($data)) {
                    return Security::satanizer($data);
                }
                return $this->_satanize($data);
            }, $xData);
        }
        return Security::satanizer($xData);
    }

    /**
     * @param array $asMethod
     * @throw HttpExceptionInterface
     * @return void
     */
    public function allowMethod(array $asMethod=[]) : void
    {
        if (empty($asMethod)) {
            throw new Exception('Allowed method is not defined !');
        }

        if (!in_array(strtolower($this->oRequest->getMethod()), array_map('strtolower', $asMethod))) {
            throw new MethodNotAllowedException();
        }
    }

    /**
     * @return string
     */
    public function getMethod() : string
    {
        return $this->oRequest->getMethod();
    }

    /**
     * @return null|string
     */
    public function getReferer() :?string
    {
        return $this->getEnv('HTTP_REFERER');
    }

    /**
     * @param string $param
     * @return null|mixed
     */
    public function getEnv(string $param)
    {
        $serverParams = $this->oRequest->getServerParams();
        return array_key_exists($param, $serverParams) ? $serverParams[$param] : null;
    }

    /**
     * @return string
     */
    public function getRequestTarget(): string
    {
        return $this->oRequest->getRequestTarget();
    }
}
