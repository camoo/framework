<?php

declare(strict_types=1);

namespace CAMOO\Console;

use CAMOO\Command\Command;
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
    public function __construct(private array $argv)
    {
    }

    public function run(): void
    {
        array_shift($this->argv);
        $this->execute($this->argv);
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
            $method = Inflector::camelize($method);
            if (!method_exists($commandClass, $method)) {
                throw new ConsoleException(sprintf('Method %s::%s not found!', get_class($commandClass), $method));
            }
            call_user_func_array([$commandClass, $method], array_values($commandClass->getCommandParam()));
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

        /** @var CommandInterface|Command $command */
        $command = di($class);

        if (!$command->isEnabled()) {
            throw new ConsoleException(sprintf('Command %s not is disabled!', $class));
        }

        return $command->initialize($name, $inp);
    }
}
