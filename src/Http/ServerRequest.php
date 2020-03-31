<?php
declare(strict_types=1);
namespace CAMOO\Http;

use \CAMOO\Utils\QueryData;
use \CAMOO\Exception\Exception;
use \CAMOO\Utils\Security;
use CAMOO\Exception\Http\MethodNotAllowedException;
use CAMOO\Exception\Http\BadRequestException;
use CAMOO\Utils\Configure;

class ServerRequest
{
    private static $_csrfSegment='Aura\Session\CsrfToken';

    private const REQUEST_METHODS = [
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
    private $session;
    public $csrf_Token = null;
    public $Flash = null;
    private $__session = [Session::class, 'create'];
    private $__flash = [Flash::class, 'create'];
    private $__cookie = [Cookie::class, 'create'];

    private $_queryDataMaps = [
        'query' => 'getQueryParams',
        'data'  => 'getParsedBody',
    ];

    public function __construct($oRequest = null)
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

        /** @var SessionSegment */
        $this->session = $oSession->segment(Session::SEG_NAME);

        /** @var SessionSegment $oCsrfSgement */
        $oCsrfSgement = $this->_getCsrfSegement($oSession);

        ################## CSRF protection
        // @See https://github.com/auraphp/Aura.Session
        if (in_array($this->oRequest->getMethod(), ['DELETE', 'POST', 'PUT', 'PATCH'])) {
            $csrfCreatedAt = (int) $oCsrfSgement->read('__csrf_created_at');
            $csrfTimeout = Configure::read('Security.csrf_lifetime') ?? 1800;

            $oCsrfToken = $oSession->getCsrfToken();
            $csrf_value = $this->_satanize($_POST['__csrf_Token']);
            if ((time() - $csrfCreatedAt) >  (int) $csrfTimeout || ! $oCsrfToken->isValid($csrf_value)) {
                throw new BadRequestException('Request Black-holed');
            }
        }

        if (!empty($this->oRequest)) {
            $this->query = $this->_satanize($this->__queryData($this->oRequest->getQueryParams()));
            $this->data = $this->_satanize($this->__queryData($this->oRequest->getParsedBody()));
        }

        if ($oCsrfSgement->check('__csrf_created_at')) {
            $oSession->getCsrfToken()->regenerateValue();
        }

        $this->csrf_Token = $oSession->getCsrfToken()->getValue();
        $oCsrfSgement->write('__csrf_created_at', time());
        ################## CSRF protection END

        $this->cookie = $this->__getCookie();
        $this->Flash = $this->__getFlash($oSession->getFlash())->initialize();
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

        if (strtolower($request_method) === 'ajax') {
            return null !== getEnv('HTTP_X_REQUESTED_WITH') && getEnv('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
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
            throw Exception('Allowed method is not defined !');
        }

        if (!in_array(strtolower($this->oRequest->getMethod()), array_map('strtolower', $asMethod))) {
            throw new MethodNotAllowedException();
        }
    }

    private function _getCsrfSegement($oSession) : SessionSegment
    {
        return new SessionSegment($oSession->segment(static::$_csrfSegment));
    }
}
