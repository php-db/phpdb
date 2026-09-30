<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

use Override;

/**
 * @api
 *
 * @extends AbstractResultSet<array<array-key, mixed>>
 */
class ArrayResultSet extends AbstractResultSet
{
    /**
     * Iterator: get current item
     *
     * @return array<array-key, mixed>|null
     *
     * @throws Exception\RuntimeException
     * @throws Exception\ValueError If a row is not row data.
     */
    #[Override]
    public function current(): ?array
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

    /**
     * @return array<array-key, mixed>
     *
     * @throws Exception\ValueError If the row is not row data.
     */
    #[Override]
    protected function mapRow(mixed $row): array
    {
        return $this->getArrayData($row);
    }
}
