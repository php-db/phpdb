<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

use Override;

use function get_debug_type;

/**
 * @api
 *
 * @extends AbstractResultSet<RowPrototypeInterface>
 */
class RowPrototypeResultSet extends AbstractResultSet implements RowPrototypeResultSetInterface
{
    public function __construct(
        private RowPrototypeInterface $rowPrototype,
    ) {}

    /**
     * Iterator: get current item
     *
     * @throws Exception\RuntimeException
     * @throws Exception\ValueError If a row is not row data.
     */
    #[Override]
    public function current(): ?RowPrototypeInterface
    {
        return $this->currentRow();
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

    /**
     * A row that already satisfies the prototype interface is the caller's own object
     * and is passed through untouched; row data populates a clone of the prototype.
     *
     * @throws Exception\ValueError If the row is neither a RowPrototypeInterface nor row data.
     */
    #[Override]
    protected function mapRow(mixed $row): RowPrototypeInterface
    {
        if ($row instanceof RowPrototypeInterface) {
            return $row;
        }

        return (clone $this->getRowPrototype())->populate($this->getArrayData($row));
    }

    /** {@inheritDoc} */
    #[Override]
    protected function unsupportedRowError(mixed $row): Exception\ValueError
    {
        return Exception\ValueError::forRowThatIsNotArrayDataOrPrototype(get_debug_type($row), static::class);
    }
}
