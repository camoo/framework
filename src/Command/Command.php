<?php

declare(strict_types=1);

namespace CAMOO\Command;

use CAMOO\Console\CommandWrapper;
use CAMOO\Interfaces\CommandInterface;
use CAMOO\Utils\Security;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class AppCommand
 *
 * @author CamooSarl
 */
abstract class Command implements CommandInterface
{
    public const SUCCESS = BaseCommand::SUCCESS;

    public const FAILURE = BaseCommand::FAILURE;

    protected SymfonyStyle $out;

    private array $param = [];

    private ?string $method = null;

    private CommandWrapper $command;

    /**
     * Call an internal method or a Symfony Command method handled by the wrapper.
     *
     * Wrap the Symfony Command PHP functions to call as method of Command object.
     */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->command->__call($method, $arguments);
    }

    public function isEnabled(): bool
    {
        return true;
    }

    final public function initialize(string $name, array $argv = []): self
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

        return $this;
    }

    public function getCommandParam(): array
    {
        return $this->satanise($this->param);
    }

    public function getCommandMethod(): ?string
    {
        return $this->satanise($this->method);
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
    private function satanise(mixed $xData): mixed
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

            return array_map(
                fn (mixed $data) => is_array($data) ? $this->satanise($data) : Security::satanizer($data),
                $xData
            );
        }

        return Security::satanizer($xData);
    }
}
