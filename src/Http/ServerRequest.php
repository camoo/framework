<?php
declare(strict_types=1);
namespace CAMOO\Http;

use \CAMOO\Utils\QueryData;
use \CAMOO\Exception\Exception;

class ServerRequest
{
    const REQUEST_METHODS = [
        'POST',
        'GET',
        'PUT',
        'DELETE',
        'PATCH'
    ];
    private $oRequest = null;
    public $query = [];
    public $data = [];
    public $cookie = [];
    public $session = [];
    public $csrf_Token = null;
    public $Flash = null;
    private $__session = [Session::class, 'create'];
    private $__flash = [Flash::class, 'create'];
    private $__cookie = [Cookie::class, 'create'];
    private $oToken;

    private $_queryDataMaps = [
        'query' => 'getQueryParams',
        'data'  => 'getParsedBody',
    ];

    public function __construct($oRequest = null)
    {
        $this->oRequest = $oRequest;
        $this->invoker();
    }

    public function __call($name, $xargs)
    {
        if (mb_substr($name, 0, 3) === 'get' && in_array(mb_strtolower(mb_substr($name, 3)), array_keys($this->_queryDataMaps))) {
            return $this->__queryData($this->oRequest->{$this->_queryDataMaps[mb_strtolower(mb_substr($name, 3))]}());
        } elseif (in_array($name, array_keys($this->_queryDataMaps))) {
            if (empty($xargs) || count($xargs) > 1 || !preg_match('/\S/', $xargs[0])) {
                throw new Exception(
                    sprintf('Method %s::%s does not exist', get_class($this), $name)
                );
            }
            return $this->__queryData($this->oRequest->{$this->_queryDataMaps[$name]}(), false)->get($xargs[0]);
        }
        throw new Exception(
            sprintf('Method %s::%s does not exist', get_class($this), $name)
        );
    }

    private function __getSession()
    {
        return call_user_func($this->__session);
    }

    private function __getFlash($oFlashSession)
    {
        return call_user_func($this->__flash, $oFlashSession);
    }

    private function __getCookie()
    {
        return call_user_func($this->__cookie);
    }

    private function __getRequest($oRequest)
    {
        return call_user_func($this->__request, $oRequest)->initialize();
    }

    private function __queryData($xData, $bAll = true)
    {
        $oxData = new QueryData($xData);
        return $bAll === true? $oxData->all() : $oxData;
    }

    private function invoker()
    {
        $oSession = $this->__getSession();
        $this->oToken = $oSession->getCsrfToken();
        $this->csrf_Token = $oSession->getCsrfToken()->getValue();
        if (!empty($this->oRequest)) {
            $this->query = $this->__queryData($this->oRequest->getQueryParams());
            $this->data = $this->__queryData($this->oRequest->getParsedBody());
        }
        $this->cookie = $this->__getCookie();
        $this->Flash = $this->__getFlash($oSession->getFlash())->initialize();
        $oSession = $oSession->segment();
        $oSession->set('csrf_camoo', $this->oToken);
        $this->session = $oSession;
    }

    /**
     * @param string $request_method
     * @throw Exception
     * @return bool
     */
    public function is(string $request_method) : bool
    {
        if (!in_array(strtoupper($request_method), static::REQUEST_METHODS)) {
            throw new Exception(sprintf('%s is not an allowed request method', $request_method));
        }

        return strtoupper(getEnv('REQUEST_METHOD')) === strtoupper($request_method);
    }
}
