<?php
declare(strict_types=1);

namespace CAMOO\Console;

use CAMOO\Exception\ConsoleException;

final class BackgroundProcess
{
    /** @var null|string $command */
    private $command;

    /** @var int $pid */
    private $pid = 0;

    public function __construct(?string $command = null)
    {
        $this->command  = $command;
    }

    /**
     * @param int $pid
     * @return void
     */
    private function setPid(int $pid) : void
    {
        $this->pid = $pid;
    }

    /**
     * @return int
     */
    public function getPid() : int
    {
        return $this->pid;
    }

    /**
     * @return bool
     */
    public function isRunning() : bool
    {
        if (($pid = $this->getPid()) > 0) {
            try {
                $result = shell_exec(sprintf('ps %d 2>&1', $pid));
                return count(preg_split("/\n/", $result)) > 2 && !preg_match('/ERROR: Process ID out of range/i', $result);
            } catch (ConsoleException $exception) {
                // Do nothing
            }
        }

        return false;
    }

    public function stop() : bool
    {
        if (($pid = $this->getPid()) > 0) {
            try {
                $result = shell_exec(sprintf('kill -9 %d 2>&1', $pid));
                return (boolean) !preg_match('/No such process/i', $result);
            } catch (ConsoleException $exception) {
                // Do nothing
            }
        }

        return false;
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
            /** @var int */
            $pid = (int) shell_exec(sprintf('%s %s %s 2>&1 & echo $!', $this->getCommand(), ($bAppend) ? '>>' : '>', $sOutputFile));
            $this->setPid($pid);
            return $pid;
        }

        throw new ConsoleException('Operating System not Supported');
    }
}
