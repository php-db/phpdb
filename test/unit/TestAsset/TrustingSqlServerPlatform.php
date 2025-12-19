<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use Override;
use PhpDb\Adapter\Platform\Sql92;

final class TrustingSqlServerPlatform extends Sql92
{
    /** @var array{string, string} */
    protected array $quoteIdentifier = ['[', ']'];

    /**
     * @param string $value
     */
    #[Override]
    public function quoteValue($value): string
    {
        return "'" . $value . "'";
    }

    #[Override]
    public function getName(): string
    {
        return 'sqlserver';
    }
}
