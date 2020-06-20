<?php
declare(strict_types=1);

namespace CAMOO\Console;

use CAMOO\Exception\ConsoleException;
use CAMOO\Utils\Inflector;
use CAMOO\Utils\Configure;
use CAMOO\Interfaces\CommandInterface;

/**
 * Class Runner
 * @author CamooSarl
 */
final class Runner
{

    /** @var CommandInterface */
    private $class;

    public function run(array $argv)
    {
        array_shift($argv);

        $data = $argv;

        $this->execute($data);
    }

    private function execute(array $inp) : void
    {
        if (empty($inp)) {
            throw new ConsoleException('Too few Parameter for Console given');
        }

        $class = array_shift($inp);
        $classClassify = sprintf('%s%s', Inflector::classify($class), 'Command');

        $this->class = $this->loadCommand($classClassify, $inp);

        if ($method = $this->class->getCommandMethod()) {
            $method = Inflector::classify($method);
            if (!method_exists($this->class, $method)) {
                throw new ConsoleException(sprintf('Method %s::%s not found!', get_class($this->class), $method));
            }
            call_user_func_array([$this->class, $method], $this->class->getCommandParam());
        } elseif (method_exists($this->class, 'main')) {
            $this->class->main();
        }
    }

    /**
     * @param string $name
     * @return CommandInterface
     */
    private function loadCommand(string $name, array $inp) : CommandInterface
    {
        $namespace = 'CAMOO\\Command\\';
        $class = $namespace . $name;

        if (!class_exists($class)) {
            $asNameSpace = explode('\\', $namespace);
            array_shift($asNameSpace);
            $nameSpace = '\\' . Configure::read('App.namespace') .'\\'. implode('\\', $asNameSpace);
            $class = $nameSpace . $name;
            if (!class_exists($class)) {
                throw new ConsoleException(sprintf('Class %s not found !', $class));
            }
        }

        return new $class($inp);
    }
}
