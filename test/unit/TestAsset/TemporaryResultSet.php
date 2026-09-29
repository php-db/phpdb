<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use Override;
use PhpDb\ResultSet\AbstractResultSet;

/**
 * A distinct concrete result set, used where a test needs to prove a prototype
 * was honoured. It extends the abstract base rather than ResultSet so that the
 * concrete result sets can stay final.
 */
final class TemporaryResultSet extends AbstractResultSet
{
    /** {@inheritDoc} */
    #[Override]
    public function toArray(): array
    {
        $return = [];
        foreach ($this as $row) {
            $return[] = $row;
        }

        return $return;
    }
}
