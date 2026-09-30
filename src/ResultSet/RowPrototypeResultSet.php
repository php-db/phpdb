<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

use Override;

use function is_array;
use function is_object;

class RowPrototypeResultSet extends AbstractResultSet implements RowPrototypeResultSetInterface
{
    public function __construct(
        private RowPrototypeInterface $rowPrototype,
    ) {}

    /**
     * Iterator: get current item
     *
     * @throws Exception\RuntimeException If a row cannot fill the prototype.
     */
    #[Override]
    public function current(): array|RowPrototypeInterface|null
    {
        $data = parent::current();

        if ($data instanceof RowPrototypeInterface) {
            // already the shape this result set promises, so hand it back untouched
            return $data;
        }

        if (is_object($data)) {
            $data = $this->rowToArray($data);
        }

        if (is_array($data)) {
            return (clone $this->getRowPrototype())->populate($data);
        }

        return $data;
    }

    /** {@inheritDoc} */
    #[Override]
    public function getRowPrototype(): RowPrototypeInterface
    {
        return $this->rowPrototype;
    }

    /** {@inheritDoc} */
    #[Override]
    public function setRowPrototype(
        RowPrototypeInterface $rowPrototype,
    ): ResultSetInterface&RowPrototypeResultSetInterface {
        $this->rowPrototype = $rowPrototype;

        return $this;
    }

    /** {@inheritDoc} */
    #[Override]
    public function toArray(): array
    {
        $return = [];
        foreach ($this as $row) {
            $return[] = $row instanceof RowPrototypeInterface ? $row->toArray() : $row;
        }

        return $return;
    }
}
