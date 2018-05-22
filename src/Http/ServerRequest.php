<?php
namespace CAMOO\Http;

use \CAMOO\Utils\QueryData;
use \CAMOO\Exception\Exception;

class ServerRequest
{

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

    public function __construct($oRequest = null)
    {
        $this->oRequest = $oRequest;
        $this->invoker();
    }

    public function __call($name, $xargs)
    {
        if (in_array($name, ['query', 'data'])) {
            if (empty($xargs) || count($xargs) > 1 || !preg_match('/\S/', $xargs[0])) {
                throw new Exception(
                    sprintf('Method %s::%s does not exist', get_class($this), $name)
                );
            }
            return $this->{$name}->get($xargs[0]);
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

    private function invoker()
    {
        $this->session = $this->__getSession()->segment();
        $this->csrf_Token = $this->__getSession()->getCsrfToken()->getValue();
        if (!empty($this->oRequest)) {
            $this->query = new QueryData($this->oRequest->getQueryParams());
            $this->data = new QueryData($this->oRequest->getParsedBody());
        }
        $this->cookie = $this->__getCookie();
        $this->Flash = $this->__getFlash($this->__getSession()->getFlash())->initialize();
    }
}
