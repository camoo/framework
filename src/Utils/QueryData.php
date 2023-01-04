<?php

declare(strict_types=1);

namespace CAMOO\Utils;

use Noodlehaus\AbstractConfig;

/**
 * Class QueryData
 *
 * @author CamooSarl
 */
class QueryData extends AbstractConfig
{
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    protected function getDefaults(): array
    {
        return [];
    }
}
