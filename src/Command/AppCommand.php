<?php
declare(strict_types=1);

namespace CAMOO\Command;

use CAMOO\Interfaces\CommandInterface;
use InvalidArgumentException;
use CAMOO\Utils\Security;

/**
 * Class AppCommand
 * @author CamooSarl
 */
class AppCommand implements CommandInterface
{
    private $param = [];
    private $method = null;

    public function __construct(array $inp=[])
    {
        if (!empty($inp)) {
            $this->method = array_shift($inp);
            $this->param = $inp;
        }
    }

    public function getCommandParam() : array
    {
        return $this->_satanize($this->param);
    }

    public function getCommandMethod() : ?string
    {
        return $this->_satanize($this->method);
    }

    public function main() : void
    {
    }

    /**
     * @param string|array $xData
     * @return string|array $xData
     */
    private function _satanize($xData)
    {
        if (is_numeric($xData)) {
            return $xData;
        }

        if (is_object($xData)) {
            throw new InvalidArgumentException('Invalid Data type! Only string|Array are allowed');
        }

        if (is_array($xData)) {
            if (count($xData) === 0) {
                return $xData;
            }
            return array_map(function ($data) {
                if (!is_array($data)) {
                    return Security::satanizer($data);
                }
                return $this->_satanize($data);
            }, $xData);
        }
        return Security::satanizer($xData);
    }
}
