<?php
declare(strict_types=1);

namespace CAMOO\Exception\Http;

use CAMOO\Interfaces\HttpExceptionInterface;
use CAMOO\Exception\Exception as BaseException;

/**
 * Class BaseHttpException
 * @author CamooSarl
 */
class BaseHttpException extends BaseException implements HttpExceptionInterface
{
}
