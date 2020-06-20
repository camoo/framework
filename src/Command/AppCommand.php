<?php
declare(strict_types=1);

namespace CAMOO\Command;
use CAMOO\Interfaces\CommandInterface;

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
        return $this->param;
    }

    public function getCommandMethod() : ?string
    {
        return $this->method;
    }

    public function main() : void
    {
    }
}
