<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

use Override;

final class ArrayResultSet extends AbstractResultSet
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
