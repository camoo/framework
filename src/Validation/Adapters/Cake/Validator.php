<?php
declare(strict_types=1);

namespace CAMOO\Validation\Adapters\Cake;

use CAMOO\Interfaces\ValidationInterface;
use Cake\Validation\Validator as BaseValidator;

/**
 * Class Validator
 * @author CamooSarl
 */
class Validator extends BaseValidator implements ValidationInterface
{
    /** @var array $xErrors */
    private $xErrors = [];

    public function __construct()
    {
        parent::__construct();
        $this->_useI18n = false;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function isValid(array $data) : bool
    {
        $this->xErrors = $this->errors($data);
        return empty($this->xErrors);
    }

    /**
     * @return array
     */
    public function getErrors() : array
    {
        return $this->xErrors;
    }
}
