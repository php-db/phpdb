<?php

namespace LaminasTest\Db\TestAsset;

use Laminas\Db\Adapter\Platform\Mysql;

class TrustingMysqlPlatform extends Mysql
{
    /**
     * @param string $value
     * @return string
     */
    public function quoteValue($value): string
    {
        return $this->quoteTrustedValue($value);
    }
}
