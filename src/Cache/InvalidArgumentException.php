<?php

namespace CAMOO\Cache;

use Cake\Core\Exception\Exception;
use Psr\SimpleCache\InvalidArgumentException as InterfaceInvalidArgument;

/**
 * Exception raised when cache keys are invalid.
 */
class InvalidArgumentException extends Exception implements InterfaceInvalidArgument
{
}
