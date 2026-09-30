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
use PhpDb\ResultSet\Exception\ValueError;
use ReturnTypeWillChange;
use Traversable;

use function count;
use function current;
use function get_debug_type;
use function is_array;
use function reset;

/**
 * @api
 *
 * @template TRow
 *
 * @implements ResultSetInterface<TRow>
 */
abstract class AbstractResultSet implements ResultSetInterface
{
    /** How deeply initialize() will unwrap a chain of IteratorAggregate. */
    private const int MAX_ITERATOR_DEPTH = 8;

    /**
     * Created on first use, so that a subclass constructor need not call this one.
     *
     * @var RowBuffer<TRow>|null
     */
    private ?RowBuffer $buffer = null;

    protected ?int $count = null;

    /**
     * Always an Iterator once initialize() has run: an IteratorAggregate is resolved
     * before it is stored, and a ResultInterface is an Iterator in its own right.
     */
    protected ?Iterator $dataSource = null;

    protected ?int $fieldCount = null;

    protected int $position = 0;

    /**
     * Resolve an IteratorAggregate chain down to the Iterator it wraps.
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

        // @codeCoverageIgnoreStart
        throw InvalidArgumentException::forNonIteratorDataSource($dataSource::class);

        // @codeCoverageIgnoreEnd
    }

    /**
     * @throws RuntimeException
     */
    public function buffer(): ResultSetInterface
    {
        $pending = $this->rowBuffer()->isPending();
        $this->rowBuffer()->store();

        if ($pending && $this->dataSource instanceof ResultInterface) {
            $this->dataSource->rewind();
        }

        return $this;
    }

    /**
     * Countable: return count of rows
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function count(): ?int
    {
        if (null !== $this->count) {
            return $this->count;
        }

        if ($this->dataSource instanceof Countable) {
            $this->count = count($this->dataSource);
        }

        return $this->count;
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
     *
     * @throws RuntimeException
     */
    #[Override]
    public function getFieldCount(): int
    {
        if (null !== $this->fieldCount) {
            return $this->fieldCount;
        }

        if (null === $this->dataSource) {
            return 0;
        }

        $dataSource = $this->dataSource();

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
        $this->rowBuffer()->clear();

        if ($dataSource instanceof ResultInterface) {
            $this->fieldCount = $dataSource->getFieldCount();
            $this->dataSource = $dataSource;
            if ($dataSource->isBuffered()) {
                $this->rowBuffer()->useDataSource();
            }

            if ($this->rowBuffer()->isStoring()) {
                $dataSource->rewind();
            }

            return $this;
        }

        if ($dataSource instanceof Traversable) {
            $this->dataSource = self::resolveIterator($dataSource);

            return $this;
        }

        $first            = current($dataSource);
        $this->fieldCount = is_array($first) || $first instanceof Countable ? count($first) : 0;
        reset($dataSource);
        $this->dataSource = new ArrayIterator($dataSource);
        $this->rowBuffer()->useDataSource(); // an array is its own buffer

        return $this;
    }

    public function isBuffered(): bool
    {
        return $this->rowBuffer()->isBuffered();
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
        $this->rowBuffer()->startIteration();

        if (! $this->rowBuffer()->isStoring() || $this->position === $this->dataSource()->key()) {
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
        if (! $this->rowBuffer()->isStoring()) {
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
        if ($this->rowBuffer()->has($this->position)) {
            return true;
        }

        return $this->dataSource()->valid();
    }

    /**
     * Shape one raw data source row into the type this result set yields.
     *
     * Implementations narrow the return type to whatever their current() declares.
     *
     * @return TRow
     */
    abstract protected function mapRow(mixed $row): mixed;

    /**
     * The row at the current position, served from the buffer when one is held there.
     *
     * Concrete result sets call this from current() and shape the row in mapRow(), so
     * that buffering lives here and row typing lives with the set that declares it.
     *
     * @return TRow|null
     *
     * @throws RuntimeException
     */
    protected function currentRow(): mixed
    {
        $this->rowBuffer()->startIteration();

        if ($this->rowBuffer()->has($this->position)) {
            return $this->rowBuffer()->get($this->position);
        }

        $dataSource = $this->dataSource();

        /**
         * The data source is asked for its row first, because a ResultInterface only
         * knows whether it is still valid once it has tried to fetch one.
         *
         * @var mixed $row
         */
        $row = $dataSource->current();

        if (! $dataSource->valid()) {
            return null;
        }

        $row = $this->mapRow($row);

        $this->rowBuffer()->put($this->position, $row);

        return $row;
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

    /**
     * The row data a prototype can be filled from.
     *
     * Only the types a result set may safely read are accepted; anything else is the
     * caller's own object, which this component refuses to reshape.
     *
     * @return array<array-key, mixed>
     *
     * @throws ValueError If the row is neither an array nor an ArrayObject.
     */
    protected function getArrayData(mixed $row): array
    {
        if (is_array($row)) {
            return $row;
        }

        if ($row instanceof ArrayObject) {
            return $row->getArrayCopy();
        }

        throw $this->unsupportedRowError($row);
    }

    /**
     * The rows this result set holds on to.
     *
     * @return RowBuffer<TRow>
     */
    protected function rowBuffer(): RowBuffer
    {
        return $this->buffer ??= new RowBuffer();
    }

    /**
     * The error describing a row this result set cannot read.
     *
     * Overridden by a result set that accepts more than row data, so that the message
     * names everything it would have taken.
     */
    protected function unsupportedRowError(mixed $row): ValueError
    {
        return ValueError::forRowThatIsNotArrayData(get_debug_type($row), static::class);
    }

    /**
     * A result set is routinely cloned from a prototype, so the rows one holds must
     * never be shared with the next.
     */
    public function __clone(): void
    {
        if (null === $this->buffer) {
            return;
        }

        $this->buffer = clone $this->buffer;
    }
}
