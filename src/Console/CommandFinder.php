<?php

declare(strict_types=1);

namespace CAMOO\Console;

use CAMOO\Utils\Inflector;
use Composer\InstalledVersions;
use DirectoryIterator;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

final class CommandFinder
{
    private string $commandDirectory;

    private array $commands = [];

    private SymfonyStyle $out;

    public function __construct()
    {
        $output = new ConsoleOutput();
        $input = new ArgvInput();
        $this->out = new SymfonyStyle($input, $output);
        $app = defined('APP') ? APP : dirname(__DIR__) . DIRECTORY_SEPARATOR;
        $this->commandDirectory = $app . 'Command';
    }

    public function find(): void
    {
        $version = InstalledVersions::getVersion('camoo/framework');
        $this->out->writeln(sprintf('<info>CAMOO FRAMEWORK Version:%s</info>', $version));
        $this->out->writeln(sprintf('<info>PHP Version:%s</info>', PHP_VERSION) . PHP_EOL);
        $this->out->writeln('<fg=black;bg=cyan>Available Commands:</>');
        $directoryCollection = new DirectoryIterator($this->commandDirectory);

        foreach ($directoryCollection as $dirPath) {
            if ($dirPath->isDot()) {
                continue;
            }

            if (!$dirPath->isFile()) {
                continue;
            }

            $file = $dirPath->getPathname();
            $item = str_replace([$this->commandDirectory, '.php', DIRECTORY_SEPARATOR, 'Command'], '', $file);
            $this->commands[] = '- ' . Inflector::tableize($item);
        }
        $this->out->writeln(implode("\n\r", $this->commands));
    }
}
