<?php
namespace CAMOO\Http;

use CAMOO\Exception\Exception;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use GuzzleHttp\RequestOptions;

/**
 * Class Http Client
 *
 */
class Client
{
    const GET_REQUEST = 'GET';
    const POST_REQUEST = 'POST';
    const PUT_REQUEST = 'PUT';
    const PATCH_REQUEST = 'PATCH';
    const DELETE_REQUEST = 'DELETE';

    const CLIENT_TIMEOUT = 10;
    /**
     * @var string
     */
    protected $endpoint;

    /**
     * @var array
     */
    protected $userAgent = array();

    /**
     * @var array
     */
    protected $hRequestVerbs = [
        self::GET_REQUEST    => ['raw' => RequestOptions::QUERY,       'json' => RequestOptions::JSON],
        self::POST_REQUEST   => ['raw' => RequestOptions::FORM_PARAMS, 'json' => RequestOptions::JSON],
        self::PUT_REQUEST    => ['raw' => RequestOptions::FORM_PARAMS, 'json' => RequestOptions::JSON],
        self::DELETE_REQUEST => ['raw' => RequestOptions::QUERY,       'json' => RequestOptions::JSON],
        self::PATCH_REQUEST  => ['raw' => RequestOptions::QUERY,       'json' => RequestOptions::JSON],
    ];

    /**
     * @var int
     */
    private $timeout = self::CLIENT_TIMEOUT;

    /**
     * @var mixed
     */
    private $hAuthentication = [];

    /**
     * @var object
     */
    private $oClient = null;

    /**
     * @param string $endpoint
     * @param int $timeout > 0
     *
     * @throws \Exception if timeout settings are invalid
     */
    public function __construct($timeout = 0)
    {

        $this->addUserAgentString('CAMOO/Client/' . CAMOO_FMW_VERSION);
        $this->addUserAgentString($this->getPhpVersion());

        if (!is_int($timeout) || $timeout < 0) {
            throw new Exception(sprintf(
                'Connection timeout must be an int >= 0, got "%s".',
                is_object($timeout) ? get_class($timeout) : gettype($timeout).' '.var_export($timeout, true)
            ));
        }
        if (!empty($timeout)) {
            $this->timeout = $timeout;
        }

        if (is_null($this->oClient)) {
            $this->oClient = new \GuzzleHttp\Client(['timeout' => $this->timeout]);
        }
    }
    /**
     * @param string $userAgent
     */
    public function addUserAgentString($userAgent)
    {
        $this->userAgent[] = $userAgent;
    }

    /**
     * @param string      $method
     * @param string|null $endpoint
     * @param string|null $payload
     * @param string|null $header
     *
     * @return array
     *
     * @throws Exception
     */
    protected function performRequest($method, $endpoint, $payload, $header)
    {
        $type=$this->_validVerb($payload, $header);
        $data = ['headers' => $header];
        if (!empty($payload)) {
            $data[$this->hRequestVerbs[$method][$type]] = $payload;
        }

        try {
            return $this->oClient->request($method, $endpoint, $data);
        } catch (RequestException $e) {
            throw new Exception(Psr7\str($e->getRequest()));
            if ($e->hasResponse()) {
                throw new Exception(Psr7\str($e->getResponse()));
            }
        }
    }

    private function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    protected function _validVerb($payload, &$header)
    {
        $type = 'raw';
        $sUserAgent = implode(' ', $this->userAgent);
        $defaultHeaders = [
            'User-Agent' => $sUserAgent,
        ];
        $header += $defaultHeaders;
        if (is_string($payload) && isJson($payload)) {
            $header['Content-Type'] ='application/json';
            $header['Accept'] ='application/json';
            $type = 'json';
        }
        return $type;
    }

    public function get($endpoint, $payload = null, $header = [])
    {
        return $this->performRequest(static::GET_REQUEST, $endpoint, $payload, $header);
    }

    public function post($endpoint, $payload = null, $header = [])
    {
        return $this->performRequest(static::POST_REQUEST, $endpoint, $payload, $header);
    }

    public function delete($endpoint, $payload = null, $header = [])
    {
        return $this->performRequest(static::DELETE_REQUEST, $endpoint, $payload, $header);
    }

    public function pacth($endpoint, $payload = null, $header = [])
    {
        return $this->performRequest(static::PATCH_REQUEST, $endpoint, $payload, $header);
    }

    public function getPhpVersion()
    {
        if (!defined('PHP_VERSION_ID')) {
            $version = explode('.', PHP_VERSION);
            define('PHP_VERSION_ID', $version[0] * 10000 + $version[1] * 100 + $version[2]);
        }
        return 'PHP/' . PHP_VERSION_ID;
    }
}
