<?php
declare(strict_types=1);

namespace CAMOO\Utils;

use \Noodlehaus\AbstractConfig;

/**
 * Class QueryData
 * @author CamooSarl
 */
class QueryData extends AbstractConfig
{
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * @return array
     */
    protected function getDefaults()
    {
        return [];
    }

    public function __get(string $key)
    {
        return $this->get($key);
    }
}
