<?php

namespace PhpDb\ResultSet;

use ArrayObject;
use Laminas\Hydrator\ArraySerializableHydrator;
use Laminas\Hydrator\HydratorInterface;

use function gettype;
use function is_array;
use function is_object;

class HydratingResultSet extends AbstractResultSet
{
    protected HydratorInterface $hydrator;

    protected ?object $objectPrototype;

    /**
     * Constructor
     */
    public function __construct(?HydratorInterface $hydrator = null, ?object $objectPrototype = null)
    {
        $defaultHydratorClass = ArraySerializableHydrator::class;
        $this->setHydrator($hydrator ?: new $defaultHydratorClass());
        $this->setObjectPrototype($objectPrototype ?: new ArrayObject());
    }

    /**
     * Set the row object prototype
     *
     * @throws Exception\InvalidArgumentException
     * @return $this Provides a fluent interface
     */
    public function setObjectPrototype(object $objectPrototype): static
    {
        if (! is_object($objectPrototype)) {
            throw new Exception\InvalidArgumentException(
                'An object must be set as the object prototype, a ' . gettype($objectPrototype) . ' was provided.'
            );
        }
        $this->objectPrototype = $objectPrototype;
        return $this;
    }

    /**
     * Get the row object prototype
     */
    public function getObjectPrototype(): ?object
    {
        return $this->objectPrototype;
    }

    /**
     * Set the hydrator to use for each row object
     *
     * @return $this Provides a fluent interface
     */
    public function setHydrator(HydratorInterface $hydrator): static
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

    /**
     * Iterator: get current item
     */
    public function current(): ?object
    {
        if ($this->buffer === null) {
            $this->buffer = -2; // implicitly disable buffering from here on
        } elseif (is_array($this->buffer) && isset($this->buffer[$this->position])) {
            return $this->buffer[$this->position];
        }
        $data    = $this->dataSource->current();
        $current = is_array($data) ? $this->hydrator->hydrate($data, clone $this->objectPrototype) : null;

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
    public function toArray(): array
    {
        $return = [];
        foreach ($this as $row) {
            $return[] = $this->hydrator->extract($row);
        }
        return $return;
    }
}
