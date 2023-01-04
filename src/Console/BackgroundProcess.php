<?php

declare(strict_types=1);

namespace CAMOO\Console;

use CAMOO\Exception\ConsoleException;

final class BackgroundProcess
{
    private int $pid = 0;

    public function __construct(private ?string $command = null)
    {
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function isRunning(): bool
    {
        $pid = $this->getPid();
        if ($pid === 0) {
            return false;
        }

        try {
            $result = shell_exec(sprintf('ps %d 2>&1', $pid));

            return count(preg_split("/\n/", $result)) > 2 &&
                !preg_match('/ERROR: Process ID out of range/i', $result);
        } catch (ConsoleException) {
            // Do nothing
        }

        return false;
    }

    public function stop(): bool
    {
        $pid = $this->getPid();
        if ($pid === 0) {
            return false;
        }
        try {
            $result = shell_exec(sprintf('kill -9 %d 2>&1', $pid));

            return !preg_match('/No such process/i', $result);
        } catch (ConsoleException) {
            // Do nothing
        }

        return false;
    }

    public function run(string $sOutputFile = '/dev/null', bool $bAppend = false): int
    {
        if ($this->getCommand() === null) {
            throw new ConsoleException('Command is missing');
        }
        $sOS = $this->getOS();
        if (empty($sOS)) {
            throw new ConsoleException('Operating System cannot be determined');
        }
        if (str_starts_with($sOS, 'WIN')) {
            shell_exec(sprintf('%s &', $this->getCommand()));

            return 0;
        } elseif ($sOS === 'LINUX' || $sOS === 'FREEBSD' || $sOS === 'DARWIN') {
            $pid = (int)shell_exec(
                sprintf(
                    '%s %s %s 2>&1 & echo $!',
                    $this->getCommand(),
                    ($bAppend) ? '>>' : '>',
                    $sOutputFile
                )
            );
            $this->setPid($pid);

            return $pid;
        }
        throw new ConsoleException('Operating System not Supported');
    }

    protected function getCommand(): ?string
    {
        if (null === $this->command) {
            return null;
        }

        return escapeshellcmd($this->command);
    }

    protected function getOS(): string
    {
        return strtoupper(PHP_OS);
    }

    private function setPid(int $pid): void
    {
        $this->pid = $pid;
    }
}
