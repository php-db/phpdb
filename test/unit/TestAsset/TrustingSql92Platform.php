<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use Override;
use PhpDb\Adapter\Platform\Sql92;

final class TrustingSql92Platform extends Sql92
{
    /**
     * {@inheritDoc}
     */
    #[Override]
    public function quoteValue($value): string
    {
        return $this->quoteTrustedValue($value);
    }
}
