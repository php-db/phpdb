<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

use Override;

use function get_debug_type;
use function is_array;

/**
 * @api
 *
 * @extends AbstractResultSet<RowPrototypeInterface>
 */
class RowPrototypeResultSet extends AbstractResultSet implements RowPrototypeResultSetInterface
{
    /** getRowPrototype(), asked once per data source rather than once per row. */
    private ?RowPrototypeInterface $resolvedRowPrototype = null;

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
        $this->rowPrototype         = $rowPrototype;
        $this->resolvedRowPrototype = null;

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

        $this->resolvedRowPrototype ??= $this->getRowPrototype();

        return (clone $this->resolvedRowPrototype)->populate(is_array($row) ? $row : $this->getArrayData($row));
    }

    #[Override]
    protected function resetResolvedConfiguration(): void
    {
        $this->resolvedRowPrototype = null;
    }

    /** {@inheritDoc} */
    #[Override]
    protected function unsupportedRowError(mixed $row): Exception\ValueError
    {
        return Exception\ValueError::forRowThatIsNotArrayDataOrPrototype(get_debug_type($row), static::class);
    }
}
