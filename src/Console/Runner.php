<?php

declare(strict_types=1);

namespace CAMOO\Console;

use CAMOO\Exception\ConsoleException;
use CAMOO\Interfaces\CommandInterface;
use CAMOO\Utils\Configure;
use CAMOO\Utils\Inflector;

/**
 * Class Runner
 *
 * @author CamooSarl
 */
final class Runner
{
    public function run(array $argv): void
    {
        array_shift($argv);
        $this->execute($argv);
    }

    private function execute(array $inp): void
    {
        if (empty($inp)) {
            throw new ConsoleException('Too few Parameter for Console given');
        }

        $class = array_shift($inp);
        $classClassify = sprintf('%s%s', Inflector::classify($class), 'Command');
        $commandClass = $this->loadCommand($classClassify, $inp);

        if ($method = $commandClass->getCommandMethod()) {
            $method = Inflector::classify($method);
            if (!method_exists($commandClass, $method)) {
                throw new ConsoleException(sprintf('Method %s::%s not found!', get_class($commandClass), $method));
            }
            call_user_func_array([$commandClass, $method], $commandClass->getCommandParam());
        } elseif (method_exists($commandClass, 'execute')) {
            $commandClass->execute();
        }
    }

    private function loadCommand(string $name, array $inp): CommandInterface
    {
        $namespace = 'CAMOO\\Command\\';
        $class = $namespace . $name;

        if (!class_exists($class)) {
            $asNameSpace = explode('\\', $namespace);
            array_shift($asNameSpace);
            $nameSpace = '\\' . Configure::read('App.namespace') . '\\' . implode('\\', $asNameSpace);
            $class = $nameSpace . $name;
            if (!class_exists($class)) {
                throw new ConsoleException(sprintf('Class %s not found !', $class));
            }
        }

        return new $class($name, $inp);
    }
}
