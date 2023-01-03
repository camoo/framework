<?php

declare(strict_types=1);

namespace CAMOO\Validation\Adapters\Cake;

use Cake\Validation\Validator as BaseValidator;
use CAMOO\Interfaces\ValidationInterface;

/**
 * Class Validator
 *
 * @author CamooSarl
 */
class Validator extends BaseValidator implements ValidationInterface
{
    private array $xErrors = [];

    public function __construct()
    {
        parent::__construct();
        $this->_useI18n = false;
    }

    public function isValid(array $data): bool
    {
        $this->xErrors = $this->validate($data);

        return empty($this->xErrors);
    }

    public function getErrors(): array
    {
        return $this->xErrors;
    }
}
