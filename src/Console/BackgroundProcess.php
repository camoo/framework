<?php
declare(strict_types=1);

namespace CAMOO\Console;

use CAMOO\Exception\ConsoleException;

final class BackgroundProcess
{
    /** @var null|string $command */
    private $command = null;

    public function __construct(?string $command = null)
    {
        $this->command  = $command;
    }

    protected function getCommand() : ?string
    {
        if (null !== $this->command) {
            return escapeshellcmd($this->command);
        }
        return null;
    }

    protected function getOS() : string
    {
        return strtoupper(PHP_OS);
    }

    public function run(string $sOutputFile = '/dev/null', bool $bAppend = false) : int
    {
        if ($this->getCommand() === null) {
            throw new ConsoleException('Command is missing');
        }

        $sOS = $this->getOS();

        if (empty($sOS)) {
            throw new ConsoleException('Operating System cannot be determined');
        }

        if (substr($sOS, 0, 3) === 'WIN') {
            shell_exec(sprintf('%s &', $this->getCommand(), $sOutputFile));
            return 0;
        } elseif ($sOS === 'LINUX' || $sOS === 'FREEBSD' || $sOS === 'DARWIN') {
            return (int) shell_exec(sprintf('%s %s %s 2>&1 & echo $!', $this->getCommand(), ($bAppend) ? '>>' : '>', $sOutputFile));
        }

        throw new ConsoleException('Operating System not Supported');
    }
}
