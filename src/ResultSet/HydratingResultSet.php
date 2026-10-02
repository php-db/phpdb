<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

use ArrayObject;
use Laminas\Hydrator\ArraySerializableHydrator;
use Laminas\Hydrator\HydratorInterface;
use Override;
use PhpDb\ResultSet\Exception\RuntimeException;

use function is_array;

/**
 * @api
 *
 * @extends AbstractResultSet<object>
 */
class HydratingResultSet extends AbstractResultSet implements HydratingResultSetInterface
{
    /** getHydrator() and getRowPrototype(), asked once per data source rather than once per row. */
    private ?HydratorInterface $resolvedHydrator = null;

    private ?object $resolvedRowPrototype = null;

    public function __construct(
        private ?HydratorInterface $hydrator = null,
        private ?object $rowPrototype = null,
    ) {}

    /**
     * Iterator: get current item
     *
     * @throws RuntimeException
     * @throws Exception\ValueError If a row is not row data.
     */
    #[Override]
    public function current(): ?object
    {
        return $this->currentRow();
    }

    /**
     * Get the hydrator to use for each row object
     */
    public function getHydrator(): HydratorInterface
    {
        return $this->hydrator ??= new ArraySerializableHydrator();
    }

    /** @deprecated use getRowPrototype() */
    public function getObjectPrototype(): object
    {
        return $this->getRowPrototype();
    }

    /** {@inheritDoc} */
    #[Override]
    public function getRowPrototype(): object
    {
        return $this->rowPrototype ??= new ArrayObject();
    }

    /**
     * Set the hydrator to use for each row object
     */
    public function setHydrator(HydratorInterface $hydrator): ResultSetInterface
    {
        $this->hydrator         = $hydrator;
        $this->resolvedHydrator = null;
        return $this;
    }

    /** @deprecated use setRowPrototype() */
    public function setObjectPrototype(object $objectPrototype): ResultSetInterface
    {
        return $this->setRowPrototype($objectPrototype);
    }

    /** {@inheritDoc} */
    #[Override]
    public function setRowPrototype(
        object $rowPrototype,
    ): ResultSetInterface&HydratingResultSetInterface {
        $this->rowPrototype         = $rowPrototype;
        $this->resolvedRowPrototype = null;
        return $this;
    }

    /**
     * Cast result set to array of arrays
     *
     * @throws Exception\ValueError If any row is not row data.
     */
    #[Override]
    public function toArray(): array
    {
        $return = [];

        /** @var object $row Every row this set yields is hydrated, or mapRow() throws. */
        foreach ($this as $row) {
            $return[] = $this->getHydrator()->extract($row);
        }

        return $return;
    }

    /**
     * Hydrate one row onto a clone of the prototype.
     *
     * @throws Exception\ValueError If the row is not row data.
     * @throws \Laminas\Hydrator\Exception\RuntimeException If the hydrator cannot fill the prototype.
     */
    #[Override]
    protected function mapRow(mixed $row): object
    {
        return ($this->resolvedHydrator ??= $this->getHydrator())->hydrate(
            is_array($row) ? $row : $this->getArrayData($row),
            clone ($this->resolvedRowPrototype ??= $this->getRowPrototype()),
        );
    }

    /**
     * Hydrated rows are entities with an identity of their own, so a buffered set hands
     * the same objects out on every pass, as laminas-db and earlier PhpDb releases did.
     */
    #[Override]
    protected function holdsMappedRows(): bool
    {
        return true;
    }

    #[Override]
    protected function resetResolvedConfiguration(): void
    {
        $this->resolvedHydrator     = null;
        $this->resolvedRowPrototype = null;
    }
}
