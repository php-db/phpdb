<?php

namespace LaminasTest\Db\TestAsset;

use Laminas\Db\Adapter\Platform\SqlServer;

final class TrustingSqlServerPlatform extends SqlServer
{
    /**
     * @param string $value
     */
    public function quoteValue($value): string
    {
        return $this->quoteTrustedValue($value);
    }
}
