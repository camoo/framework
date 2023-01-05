<?php

declare(strict_types=1);

namespace CAMOO\Interfaces;

use Symfony\Component\Console\Command\Command;

/**
 * Interface CommandInterface
 *
 * @author CamooSarl
 */
interface CommandInterface
{
    /**
     * Executes the current command.
     *
     * @return int 0 if everything went fine, or an exit code
     */
    public function execute(): int;

    public function isEnabled(): bool;
}
