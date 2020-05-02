<?php
declare(strict_types=1);

namespace CAMOO\Exception\Http;

use CAMOO\Interfaces\ExceptionInterface;

/**
 * Class MethodNotAllowedException
 * @author CamooSarl
 */
final class MethodNotAllowedException extends BaseHttpException
{

    /**
     * HTTP status code
     *
     * @const int
     */
    const HTTP_CODE = 405;

    /**
     * Error code storage;
     *
     * @const string
     */
    const ERROR = 'Method Not Allowed';

    /**
     * MethodNotAllowedException constructor.
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
