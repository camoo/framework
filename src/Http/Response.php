<?php
declare(strict_types=1);
namespace CAMOO\Http;

use CAMOO\Exception\Exception;
use GuzzleHttp\Psr7\Response as BaseResponse;

/**
 * Class Http Response
 */
final class Response extends BaseResponse
{

    /** @var array $headers */
    protected $headers = [];

    /** @var array $headerNames */
    protected $headerNames = [];

    /** @var string $content Response content */
    protected $content;

    /** @var int $statusCode HTTP status code */
    protected $statusCode;

    /** @var string $statusText HTTP status text */
    protected $statusText;

    public function __construct(
        int $statusCode = 200,
        array $headers = [],
        ?string $content = null,
        float $protocolVersion = 1.1,
        ?string $statusText = null
  ) {
        $this->statusCode = (int)$statusCode;
        $this->headers = $headers;
        $this->content = (string) $content;
        $this->protocolVersion = $protocolVersion;
        $this->statusText = $statusText;

        parent::__construct($this->statusCode, $this->headers, $this->content, $this->protocolVersion, $this->statusText);
    }

    /**
     * Returns the response body.
     *
     * @return string|null
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Returns the json response body
     *
     * @return array|mixed
     */
    public function getJson()
    {
        if (($json = json_decode((string)$this->getContent(), true)) === null || (json_last_error() !== JSON_ERROR_NONE)) {
            return [];
        }
        return $json;
    }

    /**
     * Returns the error message
     *
     * @return string
     */
    public function getError()
    {
        if (!in_array($this->getStatusCode(), [200, 201])) {
            if (($resp = $this->getJson()) && array_key_exists('error', $resp)) {
                return $resp['error'];
            }
            return 'Unknown Error';
        }
        return '';
    }

    /**
     * Sets the response body.
     *
     * @param string|null $content
     */
    public function withStringBody($content)
    {
        if ($content !== null) {
            $this->content = (string)$content;
            $this->setHeader('Content-Length', (string)strlen($this->content));
        } else {
            $this->content = null;
            unset($this->headers['content-type'], $this->headers['content-length']);
        }
        return $this;
    }

    /**
     * Sets header (overwrites existing header).
     *
     * @param string          $name
     * @param string|string[] $values
     */
    public function setHeader($name, $values)
    {
        if (!is_string($values) && !is_array($values)) {
            throw new Exception(
                'Invalid header, only string|string[] allowed'
      );
        }
        if ($values === '' || $values === []) {
            throw new Exception('Empty header not allowed');
        }
        if (!is_array($values)) {
            $values = [$values];
        }
        foreach ($values as $value) {
            if (!is_string($value) || $value === '') {
                throw new Exception('Invalid header');
            }
        }
        $normalized = $this->normalizeHeaderName($name);
        $this->headers[$normalized] = $values;
        $this->headerNames[$normalized] = $name;
    }

    /**
     * Sets header (appends existing header).
     *
     * @param string $name
     * @param string $value
     */
    public function addHeader($name, $value)
    {
        if (in_array($this->normalizeHeaderName($name), self::$oneValueHeaders, true)) {
            throw new Exception(sprintf('Cannot append header "%s".', $name));
        }
        if (!is_string($value)) {
            throw new Exception('Invalid header', 0, null, $value);
        }
        if ($value === '') {
            throw new Exception('Empty header not allowed', 0, null, $value);
        }

        $normalized = $this->normalizeHeaderName($name);
        $header = $this->getHeader($normalized);
        if (count($header) === 0) {
            $this->headerNames[$normalized] = $name;
        }

        if (count(array_intersect($header, [$value])) === 0) {
            $header[] = $value;
            $this->headers[$normalized] = $header;
        }
    }

    /**
     * Gets header as array.
     *
     * @param string $name
     *
     * @return string[]|array empty if not set
     */
    public function getHeader($name)
    {
        $normalized = $this->normalizeHeaderName($name);
        if ($this->hasHeader($normalized)) {
            return $this->headers[$normalized];
        }

        return [];
    }

    /**
     * Returns true if the header exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasHeader($name)
    {
        return array_key_exists($this->normalizeHeaderName($name), $this->headers);
    }

    /**
     * Transforms header name to normalized form.
     *
     * @param string $name
     *
     * @return string
     * @example 'HEADER-NAME' -> 'header-name'
     *
     */
    protected function normalizeHeaderName($name)
    {
        if (!preg_match('/^[a-zA-Z0-9\-]+$/', $name)) {
            throw new Exception(sprintf('Invalid character in header name "%s".', $name));
        }

        return str_replace('_', '-', strtolower($name));
    }

	/**
	 * @return string
	 */
	public function __toString() : string
	{
		return $this->getContent();
	}
}
