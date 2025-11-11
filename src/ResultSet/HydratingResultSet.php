<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

use ArrayObject;
use Laminas\Hydrator\ArraySerializableHydrator;
use Laminas\Hydrator\HydratorInterface;
use Override;

use function is_array;

class HydratingResultSet extends AbstractResultSet
{
    public function __construct(
        private HydratorInterface $hydrator = new ArraySerializableHydrator(),
        private ?object $rowPrototype       = new ArrayObject()
    ) {
    }

    /**
     * Set the hydrator to use for each row object
     *
     * @return $this Provides a fluent interface
     */
    public function setHydrator(HydratorInterface $hydrator): ResultSetInterface
    {
        $this->hydrator = $hydrator;
        return $this;
    }

    /**
     * Get the hydrator to use for each row object
     */
    public function getHydrator(): HydratorInterface
    {
        return $this->hydrator;
    }

    /** {@inheritDoc} */
    #[Override]
    public function setRowPrototype(ArrayObject $rowPrototype): ResultSetInterface
    {
        $this->rowPrototype = $rowPrototype;
        return $this;
    }

    /** {@inheritDoc} */
    #[Override]
    public function getRowPrototype(): ?object
    {
        return $this->rowPrototype;
    }

    /** @deprecated use setRowPrototype() */
    public function setObjectPrototype(object $objectPrototype): ResultSetInterface
    {
        return  $this->setRowPrototype($objectPrototype);
    }

    /** @deprecated use getRowPrototype() */
    public function getObjectPrototype(): ?object
    {
        return $this->getRowPrototype();
    }

    /**
     * Iterator: get current item
     */
    #[Override]
    public function current(): ?object
    {
        if ($this->buffer === null) {
            $this->buffer = -2; // implicitly disable buffering from here on
        } elseif (is_array($this->buffer) && isset($this->buffer[$this->position])) {
            return $this->buffer[$this->position];
        }
        $data    = $this->dataSource->current();
        $current = is_array($data) ? $this->hydrator->hydrate($data, clone $this->rowPrototype) : null;

        if (is_array($this->buffer)) {
            $this->buffer[$this->position] = $current;
        }

        return $current;
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
            $return[] = $this->hydrator->extract($row);
        }
        return $return;
    }
}
