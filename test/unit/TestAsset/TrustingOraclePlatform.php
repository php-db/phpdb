<?php

namespace PhpDbTest\TestAsset;

use PhpDb\Adapter\Platform\Oracle;
use Override;

class TrustingOraclePlatform extends Oracle
{
    /**
     * @param string $value
     */
    #[Override] public function quoteValue($value): string
    {
        return $this->quoteTrustedValue($value);
    }
}
