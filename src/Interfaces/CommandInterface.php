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
    public const SUCCESS = Command::SUCCESS;

    public const FAILURE = Command::FAILURE;

    /**
     * Executes the current command.
     *
     * @return int 0 if everything went fine, or an exit code
     */
    public function execute(): int;
}
