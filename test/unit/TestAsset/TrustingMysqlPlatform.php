<?php

namespace PhpDbTest\TestAsset;

use PhpDb\Adapter\Platform\Mysql;

final class TrustingMysqlPlatform extends Mysql
{
    /**
     * @param string $value
     */
    public function quoteValue($value): string
    {
        return $this->quoteTrustedValue($value);
    }
}
