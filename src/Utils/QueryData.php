<?php

namespace CAMOO\Utils;

use \Noodlehaus\AbstractConfig;

class QueryData extends AbstractConfig
{
    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    protected function getDefaults()
    {
        return [];
    }

    public function __get($key)
    {
        return $this->get($key);
    }
}
