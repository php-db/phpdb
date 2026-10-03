<?php

declare(strict_types=1);

namespace PhpDbTest\ResultSet\TestAsset;

use Override;
use PhpDb\ResultSet\AbstractResultSet;

/**
 * A concrete AbstractResultSet that yields rows exactly as the data source gave them.
 *
 * Buffering and iteration are what AbstractResultSet owns, so this asset declines to
 * shape rows at all, leaving those tests free of any row typing of its own.
 */
class PassThroughResultSet extends AbstractResultSet
{
    /**
     * @throws \PhpDb\ResultSet\Exception\RuntimeException
     */
    #[Override]
    public function current(): mixed
    {
        return $this->currentRow();
    }

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

    #[Override]
    protected function mapRow(mixed $row): mixed
    {
        return $row;
    }
}
