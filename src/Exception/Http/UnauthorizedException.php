<?php

declare(strict_types=1);

namespace CAMOO\Exception\Http;

use CAMOO\Interfaces\ExceptionInterface;

/**
 * Class UnauthorizedException
 *
 * @author CamooSarl
 */
final class UnauthorizedException extends BaseHttpException
{
    /**
     * HTTP status code
     *
     * @const int
     */
    public const HTTP_CODE = 401;

    /**
     * Error code storage;
     *
     * @const string
     */
    public const ERROR = 'Unauthorized';

    /**
     * UnauthorizedException constructor.
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
