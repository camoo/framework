<?php

declare(strict_types=1);

namespace CAMOO\Command;

use CAMOO\Console\CommandWrapper;
use CAMOO\Interfaces\CommandInterface;
use CAMOO\Utils\Security;
use InvalidArgumentException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class AppCommand
 *
 * @author CamooSarl
 */
abstract class AppCommand implements CommandInterface
{
    /** @var SymfonyStyle $out */
    protected $out;

    /** @var array $param */
    private $param = [];

    /** @var string|null $method */
    private $method = null;

    /** @var CommandWrapper */
    private $command;

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
     */
    public function __call(string $method, array $arguments)
    {
        return $this->command->__call($method, $arguments);
    }

    public function getCommandParam(): array
    {
        return $this->_satanise($this->param);
    }

    public function getCommandMethod(): ?string
    {
        return $this->_satanise($this->method);
    }

    /** Configures the current command. */
    protected function configure(): void
    {
    }

    /**
     * @param string|array $xData
     *
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
