<?php

namespace PhpDbTest\TestAsset;

use PhpDb\Adapter\Platform\SqlServer;

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
