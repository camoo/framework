<?php

declare(strict_types=1);

namespace CAMOO\Exception\Http;

use CAMOO\Exception\Exception as BaseException;
use CAMOO\Interfaces\HttpExceptionInterface;

/**
 * Class BaseHttpException
 *
 * @author CamooSarl
 */
class BaseHttpException extends BaseException implements HttpExceptionInterface
{
}
