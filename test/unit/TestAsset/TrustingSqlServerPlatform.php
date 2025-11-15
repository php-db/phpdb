<?php

namespace PhpDbTest\TestAsset;

use PhpDb\Adapter\Platform\Sql92;

final class TrustingSqlServerPlatform extends Sql92
{
    protected $quoteIdentifier = ['[', ']'];

    /**
     * @param string $value
     */
    public function quoteValue($value): string
    {
        return "'" . $value . "'";
    }

    public function getName(): string
    {
        return 'sqlserver';
    }
}
