<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

use ArrayObject;
use Laminas\Hydrator\ArraySerializableHydrator;
use Laminas\Hydrator\HydratorInterface;
use Override;

use function is_array;
use function is_object;

class HydratingResultSet extends AbstractResultSet
{
    public function __construct(
        private ?HydratorInterface $hydrator = null,
        private ?object $rowPrototype = null
    ) {
        parent::__construct();
    }

    /**
     * Set the hydrator to use for each row object
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
        return $this->hydrator ??= new ArraySerializableHydrator();
    }

    /** {@inheritDoc} */
    #[Override]
    public function setRowPrototype(object $rowPrototype): ResultSetInterface
    {
        $this->rowPrototype = $rowPrototype;
        return $this;
    }

    /** {@inheritDoc} */
    #[Override]
    public function getRowPrototype(): object
    {
        return $this->rowPrototype ??= new ArrayObject();
    }

    /**
     * @deprecated use setRowPrototype()
     */
    public function setObjectPrototype(object $objectPrototype): ResultSetInterface
    {
        /** @phpstan-ignore argument.type */
        return $this->setRowPrototype($objectPrototype);
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
        if ($this->bufferState === BufferState::None) {
            $this->bufferState = BufferState::Disabled;
        } elseif ($this->bufferState === BufferState::Active && isset($this->bufferData[$this->position])) {
            $buffered = $this->bufferData[$this->position];
            return is_object($buffered) ? $buffered : null;
        }
        $data    = $this->dataSource->current();
        $current = is_array($data) ? $this->getHydrator()->hydrate($data, clone $this->getRowPrototype()) : null;

        if ($this->bufferState === BufferState::Active && $this->bufferData !== null && $current !== null) {
            $this->bufferData[$this->position] = $current;
        }

        return $current;
    }

    /**
     * Cast result set to array of arrays
     *
     * @return array<int, array<string, mixed>>
     * @throws Exception\RuntimeException If any row is not castable to an array.
     */
    #[Override]
    public function toArray(): array
    {
        $return = [];
        foreach ($this as $row) {
            if (is_object($row)) {
                $return[] = $this->getHydrator()->extract($row);
            }
        }
        return $return;
    }
}
