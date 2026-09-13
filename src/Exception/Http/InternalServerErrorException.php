<?php

declare(strict_types=1);

namespace CAMOO\Exception\Http;

use CAMOO\Interfaces\ExceptionInterface;

/**
 * Class InternalServerErrorException
 *
 * @author CamooSarl
 */
final class InternalServerErrorException extends BaseHttpException
{
    /**
     * HTTP status code
     *
     * @const int
     */
    public const int HTTP_CODE = 500;

    /**
     * Error code storage;
     *
     * @const string
     */
    public const string ERROR = 'Internal Server Error';

    /**
     * InternalServerErrorException constructor.
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
        $errorDescription ??= static::ERROR;

        parent::__construct($errorDescription, static::HTTP_CODE, $errorData, $userTitle, $userMessage, $headers, $previous);
    }
}
