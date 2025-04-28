<?php

namespace LaminasTest\Db\TestAsset;

use Laminas\Db\Adapter\Platform\Sql92;
use Override;

class TrustingSql92Platform extends Sql92
{
    /**
     * {@inheritDoc}
     */
    #[Override] public function quoteValue($value): string
    {
        return $this->quoteTrustedValue($value);
    }
}
