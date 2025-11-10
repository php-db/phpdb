<?php

namespace PhpDbTest\TestAsset;

use PhpDb\Adapter\Platform\Sql92;

final class TrustingOraclePlatform extends Sql92
{
    /**
     * @param string $value
     */
    public function quoteValue($value): string
    {
        return "'" . $value . "'";
    }

    public function getName(): string
    {
        return 'oracle';
    }
}
