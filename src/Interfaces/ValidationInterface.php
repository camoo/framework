<?php

declare(strict_types=1);

namespace CAMOO\Interfaces;

/**
 * Class ValidationInterface
 *
 * @author CamooSarl
 */
interface ValidationInterface
{
    public const DEFAULT_LIB = 'Cake';

    public function isValid(array $data): bool;

    public function getErrors(): array;
}
