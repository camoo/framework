<?php

declare(strict_types=1);

namespace CAMOO\Console;

use CAMOO\Exception\ConsoleException;
use Symfony\Component\Console\Command\Command;

/**
 * Class CommandWrapper
 *
 * @author CamooSarl
 */
final class CommandWrapper extends Command
{
    /**
     * Forward the method call to Symfony Command Methods
     *
     * @throws ConsoleException When the function is not valid
     */
    public function __call(string $function, array $arguments): mixed
    {
        if (!method_exists($this, $function)) {
            throw new ConsoleException("{$function} is not a valid Message methode");
        }

        return @call_user_func_array([$this, $function], $arguments);
    }
}
