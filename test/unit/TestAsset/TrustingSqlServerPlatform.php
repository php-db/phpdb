<?php

namespace LaminasTest\Db\TestAsset;

use PhpDb\Adapter\Platform\SqlServer;
use Override;

class TrustingSqlServerPlatform extends SqlServer
{
    /**
     * @param string $value
     */
    #[Override] public function quoteValue($value): string
    {
        return $this->quoteTrustedValue($value);
    }
}
