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
     * @throws Exception\UnexpectedValueException If a row is not row data.
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
        $this->resolvedRowPrototype = $this->getRowPrototype();

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
     * @throws Exception\UnexpectedValueException If the row is neither a RowPrototypeInterface nor row data.
     */
    #[Override]
    protected function mapRow(mixed $row): RowPrototypeInterface
    {
        if ($row instanceof RowPrototypeInterface) {
            return $row;
        }

        /** @var RowPrototypeInterface $prototype Resolved by initialize() before any row is read. */
        $prototype = $this->resolvedRowPrototype;

        return (clone $prototype)->populate(is_array($row) ? $row : $this->getArrayData($row));
    }

    #[Override]
    protected function resetResolvedConfiguration(): void
    {
        $this->resolvedRowPrototype = $this->getRowPrototype();
    }

    /** {@inheritDoc} */
    #[Override]
    protected function unsupportedRowError(mixed $row): Exception\UnexpectedValueException
    {
        return Exception\UnexpectedValueException::forRowThatIsNotArrayDataOrPrototype(
            get_debug_type($row),
            static::class,
        );
    }
}
