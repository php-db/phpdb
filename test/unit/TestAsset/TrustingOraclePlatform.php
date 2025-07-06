<?php

namespace PhpDbTest\TestAsset;

use PhpDb\Adapter\Platform\Oracle;

final class TrustingOraclePlatform extends Oracle
{
    /**
     * @param string $value
     */
    public function quoteValue($value): string
    {
        return $this->quoteTrustedValue($value);
    }
}
