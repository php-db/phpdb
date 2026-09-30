<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

use Override;

use function is_object;

class ArrayResultSet extends AbstractResultSet
{
    /**
     * Iterator: get current item
     *
     * @return array<array-key, mixed>|null
     *
     * @throws Exception\RuntimeException
     */
    #[Override]
    public function current(): ?array
    {
        $data = parent::current();

        if (is_object($data)) {
            return $this->rowToArray($data);
        }

        return $data;
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
}
