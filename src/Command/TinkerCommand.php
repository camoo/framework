<?php

declare(strict_types=1);

namespace CAMOO\Command;

use CAMOO\Exception\ConsoleException;

/** Starts an interactive PsySH session for development. */
final class TinkerCommand extends Command
{
    public function execute(): int
    {
        if (!class_exists(\Psy\Shell::class)) {
            throw new ConsoleException(
                'Tinker requires PsySH. Install it with: composer require --dev psy/psysh',
            );
        }

        $shell = new \Psy\Shell(new \Psy\Configuration());
        $shell->run();

        return self::SUCCESS;
    }
}
