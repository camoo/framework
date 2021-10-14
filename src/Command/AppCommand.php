<?php
declare(strict_types=1);

namespace CAMOO\Command;

use CAMOO\Interfaces\CommandInterface;
use InvalidArgumentException;
use CAMOO\Utils\Security;
use Symfony\Component\Console\Style\SymfonyStyle;
use CAMOO\Console\CommandWrapper;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Input\ArgvInput;

/**
 * Class AppCommand
 *
 * @author CamooSarl
 */
abstract class AppCommand implements CommandInterface
{
    /** @var array $param */
    private $param = [];

    /** @var null|string $method */
    private $method = null;

    /** @var CommandWrapper */
    private $command;

    /** @var SymfonyStyle $out */
    protected $out;

    /**
     * @param string $name
     * @param array $argv
     */
    public function __construct(string $name, array $argv = [])
    {
        $inp = array_merge([$name], $argv);
        $this->command = new CommandWrapper($name);

        $this->configure();

        $output = new ConsoleOutput();
        $input = new ArgvInput($inp, $this->getDefinition());
        $this->out = new SymfonyStyle($input, $output);

        $arguments = $input->getArguments();

        if (!empty($arguments)) {
            $this->method = array_shift($arguments);
            $this->param = $arguments;
        }
    }

    /**
     * Call an internal method or a Symfony Command method handled by the wrapper.
     *
     * Wrap the Symfony Command PHP functions to call as method of Command object.
     *
     * @param  string       $method
     * @param  array        $arguments
     *
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        return $this->command->__call($method, $arguments);
    }

    /**
     * Configures the current command.
     *
     * @return void
     */
    protected function configure() : void
    {
    }

    public function getCommandParam() : array
    {
        return $this->_satanise($this->param);
    }

    public function getCommandMethod() : ?string
    {
        return $this->_satanise($this->method);
    }

    /**
     * @param string|array $xData
     * @return string|array $xData
     */
    private function _satanise($xData)
    {
        if (is_numeric($xData)) {
            return $xData;
        }

        if (is_object($xData)) {
            throw new InvalidArgumentException('Invalid Data type! Only string|Array are allowed');
        }

        if (is_array($xData)) {
            if (count($xData) === 0) {
                return $xData;
            }
            return array_map(function ($data) {
                if (!is_array($data)) {
                    return Security::satanizer($data);
                }
                return $this->_satanise($data);
            }, $xData);
        }
        return Security::satanizer($xData);
    }
}
