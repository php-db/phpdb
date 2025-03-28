<?php

namespace LaminasTest\Db\TestAsset;

use Laminas\Db\Adapter\Platform\SqlServer;

class TrustingSqlServerPlatform extends SqlServer
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
