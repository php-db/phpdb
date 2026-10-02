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

    private RowBufferState $bufferState = RowBufferState::Pending;

    /**
     * Whether rows are read straight from the data source, with no buffer work per row.
     *
     * True for Passthrough and Disabled, which are only ever entered once a data source
     * is in place, so valid(), current() and next() can go straight to it. Derived from
     * $bufferState and written only by setBufferState(), so the two cannot disagree.
     */
    private bool $readsDirectly = false;

    /**
     * Whether rows are held as they are read, with a data source in place to read them from.
     *
     * Derived from $bufferState and the data source by setBufferState(), which
     * initialize() calls again whenever it replaces the data source.
     */
    private bool $storesRows = false;

    /**
     * Raw rows held for later passes, keyed by position, and mapped afresh on each read
     * so that no pass sees what a caller did to the rows of another.
     *
     * Only filled while Storing, and emptied by initialize() before the state can
     * leave Storing, so a row found here never needs the state checked as well.
     *
     * @var array<int, mixed>
     */
    private array $bufferedRows = [];

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
        if (RowBufferState::Disabled === $this->bufferState) {
            throw RuntimeException::forUnbufferedIteration();
        }

        if (RowBufferState::Pending !== $this->bufferState) {
            return $this;
        }

        $this->setBufferState(RowBufferState::Storing);

        if ($this->dataSource instanceof ResultInterface) {
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
        $this->bufferedRows = [];

        if ($dataSource instanceof ResultInterface) {
            $this->initializeFromResult($dataSource);

            return $this;
        }

        if ($dataSource instanceof Traversable) {
            $this->dataSource = self::resolveIterator($dataSource);
            $this->setBufferState($this->bufferState);

            return $this;
        }

        $first            = current($dataSource);
        $this->fieldCount = is_array($first) || $first instanceof Countable ? count($first) : 0;
        reset($dataSource);
        $this->dataSource = new ArrayIterator($dataSource);
        // An array is its own buffer
        $this->setBufferState(RowBufferState::Passthrough);

        return $this;
    }

    public function isBuffered(): bool
    {
        return RowBufferState::Storing === $this->bufferState || RowBufferState::Passthrough === $this->bufferState;
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
        if ($this->readsDirectly) {
            /** @var Iterator $dataSource */
            $dataSource = $this->dataSource;
            $dataSource->next();
            $this->position++;
            return;
        }

        if ($this->storesRows) {
            /** @var Iterator $dataSource */
            $dataSource = $this->dataSource;
            if ($this->position === $dataSource->key()) {
                $dataSource->next();
            }

            $this->position++;
            return;
        }

        $dataSource = $this->dataSource ?? throw RuntimeException::forUninitialisedDataSource();

        // While Storing, a pass over held rows leaves the data source where it stopped
        if (RowBufferState::Storing !== $this->bufferState || $this->position === $dataSource->key()) {
            $dataSource->next();
        }

        if (RowBufferState::Pending === $this->bufferState) {
            $this->setBufferState(RowBufferState::Disabled);
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
        if (RowBufferState::Storing !== $this->bufferState) {
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
        if ($this->readsDirectly) {
            /** @var Iterator $dataSource */
            $dataSource = $this->dataSource;
            return $dataSource->valid();
        }

        if (null !== ($this->bufferedRows[$this->position] ?? null)) {
            return true;
        }

        return ($this->dataSource ?? throw RuntimeException::forUninitialisedDataSource())->valid();
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
     * Every row of every pass comes through here, so the buffered path stays inline
     * rather than being split out to meet the complexity limit.
     *
     * A driver Result signals the end of its rows with null or false, and asking it
     * valid() afterwards may fetch again from a statement it has already closed, so
     * either value ends the read on its own.
     *
     * @return TRow|null
     *
     * @throws RuntimeException
     *
     * @mago-expect lint:halstead
     */
    protected function currentRow(): mixed
    {
        if ($this->readsDirectly) {
            /** @var Iterator $dataSource */
            $dataSource = $this->dataSource;

            /** @var mixed $row */
            $row = $dataSource->current();

            return null === $row || false === $row ? null : $this->mapRow($row);
        }

        if ($this->storesRows) {
            /** @var Iterator $dataSource */
            $dataSource = $this->dataSource;

            /** @var mixed $row */
            $row = $this->bufferedRows[$this->position] ?? $dataSource->current();

            if (null === $row || false === $row) {
                return null;
            }

            $this->bufferedRows[$this->position] = $row;

            return $this->mapRow($row);
        }

        $dataSource = $this->dataSource ?? throw RuntimeException::forUninitialisedDataSource();

        if (RowBufferState::Pending === $this->bufferState) {
            $this->setBufferState(RowBufferState::Disabled);
        }

        /** @var mixed $row */
        $row = $this->bufferedRows[$this->position] ?? $dataSource->current();

        if (null === $row || false === $row) {
            return null;
        }

        if (RowBufferState::Storing === $this->bufferState) {
            $this->bufferedRows[$this->position] = $row;
        }

        return $this->mapRow($row);
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
     * The error describing a row this result set cannot read.
     *
     * Overridden by a result set that accepts more than row data, so that the message
     * names everything it would have taken.
     */
    protected function unsupportedRowError(mixed $row): ValueError
    {
        return ValueError::forRowThatIsNotArrayData(get_debug_type($row), static::class);
    }

    private function initializeFromResult(ResultInterface $result): void
    {
        $this->fieldCount = $result->getFieldCount();
        $this->dataSource = $result;

        if ($result->isBuffered()) {
            $this->setBufferState(RowBufferState::Passthrough);
            return;
        }

        $this->setBufferState($this->bufferState);

        if (RowBufferState::Storing === $this->bufferState) {
            $result->rewind();
        }
    }

    private function setBufferState(RowBufferState $state): void
    {
        $this->bufferState   = $state;
        $this->readsDirectly = RowBufferState::Passthrough === $state || RowBufferState::Disabled === $state;
        $this->storesRows    = RowBufferState::Storing === $state && null !== $this->dataSource;
    }
}
