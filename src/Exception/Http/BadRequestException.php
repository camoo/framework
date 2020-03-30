<?php
declare(strict_types=1);

namespace CAMOO\Exception\Http;

use CAMOO\Exception\Exception as BaseHttpException;
use CAMOO\Interfaces\ExceptionInterface;

/**
 * Class BadRequestException
 * @author CamooSarl
 */
final class BadRequestException extends BaseHttpException
{

    /**
     * HTTP status code
     *
     * @const int
     */
    const HTTP_CODE = 400;

    /**
     * Error code storage;
     *
     * @const string
     */
    const ERROR = 'Bad Request';

    /**
     * BadRequestException constructor.
     *
     * @param string|null     $errorDescription
     * @param array           $errorData
     * @param string|null     $userTitle
     * @param null            $userMessage
     * @param array           $headers
     * @param ExceptionInterface|null $previous
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
