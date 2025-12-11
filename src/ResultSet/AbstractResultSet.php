<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

use ArrayIterator;
use ArrayObject;
use Countable;
use Exception;
use Iterator;
use IteratorAggregate;
use Override;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\ResultSet\Exception\InvalidArgumentException;
use PhpDb\ResultSet\Exception\RuntimeException;
use ReturnTypeWillChange;

use function count;
use function current;
use function gettype;
use function is_array;
use function is_object;
use function key;
use function method_exists;
use function reset;

abstract class AbstractResultSet implements ResultSetInterface
{
    /**
     * if -1, datasource is already buffered
     * if -2, implicitly disabling buffering in ResultSet
     * if false, explicitly disabled
     * if null, default state - nothing, but can buffer until iteration started
     * if array, already buffering
     */
    protected int|array|bool|null $buffer = null;

    protected ?int $count = null;

    protected Iterator|IteratorAggregate|ResultInterface|null $dataSource = null;

    protected ?int $fieldCount = null;

    protected int $position = 0;

    /**
     * Set the data source for the result set
     *
     * @throws InvalidArgumentException|Exception
     */
    #[Override]
    public function initialize(iterable $dataSource): ResultSetInterface
    {
        // reset buffering
        if (is_array($this->buffer)) {
            $this->buffer = [];
        }

        if ($dataSource instanceof ResultInterface) {
            $this->fieldCount = $dataSource->getFieldCount();
            $this->dataSource = $dataSource;
            if ($dataSource->isBuffered()) {
                $this->buffer = -1;
            }

            if (is_array($this->buffer)) {
                $this->dataSource->rewind();
            }

            return $this;
        }

        if (is_array($dataSource)) {
            // its safe to get numbers from an array
            $first = current($dataSource);
            reset($dataSource);
            $this->fieldCount = $first === false ? 0 : count($first);
            $this->dataSource = new ArrayIterator($dataSource);
            $this->buffer     = -1; // array's are a natural buffer
        } elseif ($dataSource instanceof IteratorAggregate) {
            /** @phpstan-ignore assign.propertyType */
            $this->dataSource = $dataSource->getIterator();
        } elseif ($dataSource instanceof Iterator) {
            $this->dataSource = $dataSource;
        } else {
            throw new InvalidArgumentException(
                'DataSource provided is not an array, nor does it implement Iterator or IteratorAggregate'
            );
        }

        return $this;
    }

    /**
     * @throws RuntimeException
     */
    public function buffer(): ResultSetInterface
    {
        if ($this->buffer === -2) {
            throw new RuntimeException('Buffering must be enabled before iteration is started');
        } elseif ($this->buffer === null) {
            $this->buffer = [];
            if ($this->dataSource instanceof ResultInterface) {
                $this->dataSource->rewind();
            }
        }

        return $this;
    }

    public function isBuffered(): bool
    {
        return $this->buffer === -1 || is_array($this->buffer);
    }

    /**
     * Get the data source used to create the result set
     */
    public function getDataSource(): ResultInterface|IteratorAggregate|Iterator|null
    {
        return $this->dataSource;
    }

    /**
     * Retrieve count of fields in individual rows of the result set
     */
    #[Override]
    public function getFieldCount(): mixed
    {
        if (null !== $this->fieldCount) {
            return $this->fieldCount;
        }

        $dataSource = $this->getDataSource();
        if (null === $dataSource) {
            return 0;
        }

        $dataSource->rewind();
        if (! $dataSource->valid()) {
            $this->fieldCount = 0;
            return 0;
        }

        $row = $dataSource->current();
        if ($row instanceof Countable) {
            $this->fieldCount = $row->count();
            return $this->fieldCount;
        }

        $row              = (array) $row;
        $this->fieldCount = count($row);
        return $this->fieldCount;
    }

    /**
     * Iterator: move pointer to next item
     */
    #[Override]
    public function next(): void
    {
        if ($this->buffer === null) {
            $this->buffer = -2; // implicitly disable buffering from here on
        }

        if (! is_array($this->buffer) || $this->position === $this->dataSource->key()) {
            $this->dataSource->next();
        }

        $this->position++;
    }

    /**
     * Iterator: retrieve current key
     */
    #[Override]
    public function key(): int
    {
        return $this->position;
    }

    /**
     * Iterator: get current item
     */
    #[Override]
    public function current(): array|object|null
    {
        if (-1 === $this->buffer) {
            // datasource was an array when the resultset was initialized
            return $this->dataSource->current();
        }

        if ($this->buffer === null) {
            $this->buffer = -2; // implicitly disable buffering from here on
        } elseif (is_array($this->buffer) && isset($this->buffer[$this->position])) {
            return $this->buffer[$this->position];
        }

        $data = $this->dataSource->current();
        if (is_array($this->buffer)) {
            $this->buffer[$this->position] = $data;
        }

        return is_array($data) ? $data : null;
    }

    /**
     * Iterator: is pointer valid?
     */
    #[Override]
    public function valid(): bool
    {
        if (is_array($this->buffer) && isset($this->buffer[$this->position])) {
            return true;
        }

        if ($this->dataSource instanceof Iterator) {
            return $this->dataSource->valid();
        } else {
            $key = key($this->dataSource);
            return $key !== null;
        }
    }

    /**
     * Iterator: rewind
     */
    #[Override]
    public function rewind(): void
    {
        if (! is_array($this->buffer)) {
            if ($this->dataSource instanceof Iterator) {
                $this->dataSource->rewind();
            } else {
                reset($this->dataSource);
            }
        }

        $this->position = 0;
    }

    /**
     * Countable: return count of rows
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function count(): ?int
    {
        if ($this->count !== null) {
            return $this->count;
        }

        if ($this->dataSource instanceof Countable) {
            $this->count = count($this->dataSource);
        }

        return $this->count;
    }

    /**
     * Cast result set to array of arrays
     *
     * @throws RuntimeException If any row is not castable to an array.
     */
    public function toArray(): array
    {
        $return = [];
        foreach ($this as $row) {
            if (is_array($row)) {
                $return[] = $row;
                continue;
            }

            if (
                ! is_object($row)
                || (
                    ! method_exists($row, 'toArray')
                    && ! method_exists($row, 'getArrayCopy')
                )
            ) {
                throw new RuntimeException(
                    'Rows as part of this DataSource, with type ' . gettype($row) . ' cannot be cast to an array'
                );
            }

            $return[] = method_exists($row, 'toArray') ? $row->toArray() : $row->getArrayCopy();
        }

        return $return;
    }

    /**
     * Set the row object prototype
     */
    abstract public function setRowPrototype(ArrayObject $rowPrototype): ResultSetInterface;

    /**
     * Get the row object prototype
     */
    abstract public function getRowPrototype(): ?object;
}
