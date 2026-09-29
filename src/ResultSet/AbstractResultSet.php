<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

use ArrayIterator;
use Countable;
use Exception;
use Iterator;
use IteratorAggregate;
use Override;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\ResultSet\Exception\InvalidArgumentException;
use PhpDb\ResultSet\Exception\RuntimeException;
use Traversable;

use function array_key_exists;
use function count;
use function current;
use function is_array;
use function reset;

/**
 * @api
 */
abstract class AbstractResultSet implements ResultSetInterface
{
    /** How deeply initialize() will unwrap a chain of IteratorAggregate. */
    private const int MAX_ITERATOR_DEPTH = 8;

    /**
     * if -1, datasource is already buffered
     * if -2, implicitly disabling buffering in ResultSet
     * if false, explicitly disabled
     * if null, default state - nothing, but can buffer until iteration started
     * if array, already buffering
     *
     * @var int|array<int, mixed>|bool|null
     */
    protected int|array|bool|null $buffer = null;

    protected ?int $count = null;

    protected ?Iterator $dataSource = null;

    protected ?int $fieldCount = null;

    protected int $position = 0;

    /**
     * Resolve an IteratorAggregate chain down to the Iterator it wraps.
     *
     * IteratorAggregate::getIterator() is declared to return Traversable, so it may
     * hand back another aggregate. Anything that never bottoms out in an Iterator is
     * rejected rather than stored, since the result set can only iterate an Iterator.
     *
     * @throws InvalidArgumentException
     * @throws Exception If the data source raises one while handing over its iterator.
     */
    private static function resolveIterator(Traversable $dataSource): Iterator
    {
        $depth = 0;
        while ($dataSource instanceof IteratorAggregate) {
            if (++$depth > self::MAX_ITERATOR_DEPTH) {
                throw InvalidArgumentException::forUnresolvableIterator(self::MAX_ITERATOR_DEPTH);
            }

            $dataSource = $dataSource->getIterator();
        }

        if ($dataSource instanceof Iterator) {
            return $dataSource;
        }

        throw InvalidArgumentException::forNonIteratorDataSource($dataSource::class);
    }

    /**
     * @throws RuntimeException
     */
    public function buffer(): ResultSetInterface
    {
        if (-2 === $this->buffer) {
            throw RuntimeException::forUnbufferedIteration();
        }

        if (null === $this->buffer) {
            $this->buffer = [];
            if ($this->dataSource instanceof ResultInterface) {
                $this->dataSource->rewind();
            }
        }

        return $this;
    }

    /**
     * Countable: return count of rows
     */
    #[Override]
    public function count(): int
    {
        if (null !== $this->count) {
            return $this->count;
        }

        if ($this->dataSource instanceof Countable) {
            $this->count = count($this->dataSource);

            return $this->count;
        }

        return 0;
    }

    /**
     * Iterator: get current item
     *
     * @throws RuntimeException
     */
    #[Override]
    public function current(): array|object|null
    {
        if (-1 === $this->buffer) {
            // datasource was an array when the resultset was initialized
            return $this->dataSource()->current();
        }

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

        $data = $this->dataSource()->current();
        if (is_array($this->buffer)) {
            $this->buffer[$this->position] = $data;
        }

        return is_array($data) ? $data : null;
    }

    /**
     * Get the data source used to create the result set
     */
    public function getDataSource(): ?Iterator
    {
        return $this->dataSource;
    }

    /**
     * Retrieve count of fields in individual rows of the result set
     */
    #[Override]
    public function getFieldCount(): int
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

        if ($dataSource instanceof Traversable) {
            $this->dataSource = self::resolveIterator($dataSource);

            return $this;
        }

        // the array is safe to measure, but its first row can be any shape at all
        $first            = current($dataSource);
        $this->fieldCount = is_array($first) || $first instanceof Countable ? count($first) : 0;
        reset($dataSource);
        $this->dataSource = new ArrayIterator($dataSource);
        $this->buffer     = -1; // array's are a natural buffer

        return $this;
    }

    public function isBuffered(): bool
    {
        return $this->buffer === -1 || is_array($this->buffer);
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
     * Iterator: move pointer to next item
     *
     * @throws RuntimeException
     */
    #[Override]
    public function next(): void
    {
        if (null === $this->buffer) {
            $this->buffer = -2; // implicitly disable buffering from here on
        }

        if (! is_array($this->buffer) || $this->position === $this->dataSource()->key()) {
            $this->dataSource()->next();
        }

        $this->position++;
    }

    /**
     * Iterator: rewind
     *
     * @throws RuntimeException
     */
    #[Override]
    public function rewind(): void
    {
        if (! is_array($this->buffer)) {
            $this->dataSource()->rewind();
        }

        $this->position = 0;
    }

    /**
     * Iterator: is pointer valid?
     *
     * @throws RuntimeException
     */
    #[Override]
    public function valid(): bool
    {
        if (
            is_array($this->buffer)
            && array_key_exists($this->position, $this->buffer)
            && null !== $this->buffer[$this->position]
        ) {
            return true;
        }

        return $this->dataSource()->valid();
    }

    /**
     * The data source, once initialize() has supplied one.
     *
     * @throws RuntimeException
     */
    protected function dataSource(): Iterator
    {
        if (null === $this->dataSource) {
            throw RuntimeException::forUninitialisedDataSource();
        }

        return $this->dataSource;
    }
}
