<?php
declare(strict_types=1);

namespace CAMOO\Console;

use Symfony\Component\Console\Command\Command;
use CAMOO\Exception\ConsoleException;

/**
 * Class CommandWrapper
 *
 * @author CamooSarl
 */
class CommandWrapper extends Command
{

    /**
     * Forward the method call to Symfony Command Methodes
     *
     * @param  string       $function
     * @param  array        $arguments
     *
     * @throws ConsoleException When the function is not valid
     *
     * @return mixed
     */
    public function __call(string $function, array $arguments)
    {
        if (!method_exists($this, $function)) {
            throw new ConsoleException("{$function} is not a valid Message methode");
        }
        return @call_user_func_array([$this,$function], $arguments);
    }
}
