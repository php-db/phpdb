<?php

namespace LaminasTest\Db\TestAsset;

use Laminas\Db\Adapter\Platform\Mysql;
use Override;

class TrustingMysqlPlatform extends Mysql
{
    /**
     * @param string $value
     */
    #[Override] public function quoteValue($value): string
    {
        return $this->quoteTrustedValue($value);
    }
}
