<?php

declare(strict_types=1);

namespace CAMOO\Exception;

use CAMOO\Interfaces\ExceptionInterface;
use RuntimeException as BaseException;
use Throwable;

class Exception extends BaseException implements ExceptionInterface
{
    /**
     * HTTP Status code.
     *
     * @var int
     */
    protected $statusCode;

    /**
     * Http headers to pass to the response.
     *
     * @var array
     */
    protected $httpHeaders;

    /**
     * The error description.
     *
     * @var string
     */
    protected $errorDescription;

    /**
     * Error data that an application can use to run logic.
     *
     * @var array|string|null
     */
    protected $errorData;

    /**
     * An error title that can be presented to the user.
     *
     * @var string
     */
    protected $userTitle;

    /**
     * A user friendly error message.
     *
     * If it is an array, the keys will be the field names, and the value the error for those fields.
     *
     * @var array|string|null
     */
    protected $userMessage;

    /**
     * Exception constructor.
     *
     * @param string          $errorDescription
     * @param string          $userTitle
     * @param string          $userMessage
     * @param \Exception|null $previous
     */
    public function __construct(
        ?string $errorDescription = null,
        int $statusCode = 500,
        array $errorData = [],
        ?string $userTitle = null,
        ?string $userMessage = null,
        array $httpHeaders = [],
        ?Throwable $previous = null
    ) {
        $this->errorDescription = $errorDescription ?? 'Internal Server Error';
        $this->errorData = $errorData;
        $this->userTitle = $userTitle ?? 'Ooops!!!';
        $this->userMessage = $userMessage ?? 'Seems one of our developers unplugged the server again!';
        $this->httpHeaders = $httpHeaders;
        $this->statusCode = $statusCode;

        $originalMessage = $this->errorDescription;
        $originalCode = $statusCode;

        parent::__construct($originalMessage, $originalCode, $previous);
    }

    /**
     * Get the HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set the HTTP status code.
     */
    public function setStatusCode(int $statusCode): Exception
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Return HTTP headers.
     *
     * @return array
     */
    public function getHttpHeaders()
    {
        return $this->httpHeaders;
    }

    /**
     * Set the HTTP headers.
     */
    public function setHttpHeaders(array $httpHeaders): Exception
    {
        $this->httpHeaders = $httpHeaders;

        return $this;
    }

    /**
     * Get the error description.
     */
    public function getErrorDescription(): string
    {
        return $this->errorDescription;
    }

    /**
     * Set the error description.
     */
    public function setErrorDescription(string $errorDescription): Exception
    {
        $this->errorDescription = $errorDescription;

        return $this;
    }

    /**
     * Get error data.
     *
     * @return array|string|null
     */
    public function getErrorData()
    {
        return $this->errorData;
    }

    /**
     * Set error data.
     *
     * @param array|string|null $errorData
     *
     * @return Exception
     */
    public function setErrorData($errorData)
    {
        $this->errorData = $errorData;

        return $this;
    }

    /**
     * Get user title.
     */
    public function getUserTitle(): string
    {
        return $this->userTitle;
    }

    /**
     * Set user title.
     */
    public function setUserTitle(string $userTitle): Exception
    {
        $this->userTitle = $userTitle;

        return $this;
    }

    /**
     * Get user message.
     *
     * @return array|string|null
     */
    public function getUserMessage()
    {
        return $this->userMessage;
    }

    /**
     * Set user message.
     *
     * @param array|string|null $userMessage
     *
     * @return Exception
     */
    public function setUserMessage($userMessage)
    {
        $this->userMessage = $userMessage;

        return $this;
    }
}
