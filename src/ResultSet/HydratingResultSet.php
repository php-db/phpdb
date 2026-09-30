<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

use ArrayObject;
use Laminas\Hydrator\ArraySerializableHydrator;
use Laminas\Hydrator\HydratorInterface;
use Override;
use PhpDb\ResultSet\Exception\RuntimeException;

use function array_key_exists;
use function get_debug_type;
use function is_array;
use function is_object;

class HydratingResultSet extends AbstractResultSet implements HydratingResultSetInterface
{
    public function __construct(
        private ?HydratorInterface $hydrator = null,
        private ?object $rowPrototype = null,
    ) {}

    /**
     * Iterator: get current item
     *
     * @throws RuntimeException
     */
    #[Override]
    public function current(): ?object
    {
        if (null === $this->buffer) {
            $this->buffer = -2; // implicitly disable buffering from here on
        }

        if (
            is_array($this->buffer)
            && array_key_exists($this->position, $this->buffer)
            && null !== $this->buffer[$this->position]
        ) {
            return $this->buffer[$this->position];
        }

        $current = $this->hydrateRow($this->dataSource()->current());

        if (is_array($this->buffer)) {
            $this->buffer[$this->position] = $current;
        }

        return $current;
    }

    /**
     * Get the hydrator to use for each row object
     */
    public function getHydrator(): HydratorInterface
    {
        return $this->hydrator ??= new ArraySerializableHydrator();
    }

    /** @deprecated use getRowPrototype() */
    public function getObjectPrototype(): ?object
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
        $this->hydrator = $hydrator;
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
        $this->rowPrototype = $rowPrototype;
        return $this;
    }

    /**
     * Cast result set to array of arrays
     *
     * @throws Exception\RuntimeException If any row is not castable to an array.
     */
    #[Override]
    public function toArray(): array
    {
        $return = [];
        foreach ($this as $row) {
            if (! is_object($row)) {
                throw RuntimeException::forUnhydratableRow(get_debug_type($row));
            }

            $return[] = $this->getHydrator()->extract($row);
        }

        return $return;
    }

    /**
     * Hydrate one row onto a clone of the prototype.
     *
     * A row that is neither an array nor reducible to one carries nothing to hydrate,
     * which is how FETCH_BOUND arrives.
     *
     * @throws RuntimeException
     */
    private function hydrateRow(mixed $data): ?object
    {
        if (is_object($data)) {
            $data = $this->rowToArray($data);
        }

        if (! is_array($data)) {
            return null;
        }

        return $this->getHydrator()->hydrate($data, clone $this->getRowPrototype());
    }
}
