<?php

namespace LaminasTest\Db\TestAsset;

use Laminas\Db\Adapter\Platform\Sql92;

final class TrustingSql92Platform extends Sql92
{
    /**
     * {@inheritDoc}
     */
    public function quoteValue($value): string
    {
        return $this->quoteTrustedValue($value);
    }
}
