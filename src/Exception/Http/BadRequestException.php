<?php

declare(strict_types=1);

namespace CAMOO\Exception\Http;

use CAMOO\Interfaces\ExceptionInterface;

/**
 * Class BadRequestException
 *
 * @author CamooSarl
 */
final class BadRequestException extends BaseHttpException
{
    /**
     * HTTP status code
     *
     * @const int
     */
    public const HTTP_CODE = 400;

    /**
     * Error code storage;
     *
     * @const string
     */
    public const ERROR = 'Bad Request';

    /**
     * BadRequestException constructor.
     *
     * @param null $userMessage
     */
    public function __construct(
        ?string $errorDescription = null,
        array $errorData = [],
        ?string $userTitle = null,
        ?string $userMessage = null,
        array $headers = [],
        ?ExceptionInterface $previous = null
    ) {
        $errorDescription = $errorDescription ?? static::ERROR;

        parent::__construct($errorDescription, static::HTTP_CODE, $errorData, $userTitle, $userMessage, $headers, $previous);
    }
}
