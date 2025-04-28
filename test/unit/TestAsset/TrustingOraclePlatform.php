<?php

namespace LaminasTest\Db\TestAsset;

use Laminas\Db\Adapter\Platform\Oracle;
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
