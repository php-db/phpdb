<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

use Countable;
use Exception;
use Iterator;
use IteratorAggregate;
use Override;
use PhpDb\Adapter\Driver\ArrayResult;
use PhpDb\Adapter\Driver\EmptyResult;
use PhpDb\Adapter\Driver\IteratorResult;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\ResultSet\Exception\InvalidArgumentException;
use PhpDb\ResultSet\Exception\RuntimeException;
use ReturnTypeWillChange;

use function count;
use function current;
use function gettype;
use function is_array;
use function method_exists;
use function reset;

abstract class AbstractResultSet implements ResultSetInterface
{
    protected BufferState $bufferState = BufferState::None;

    /** @var array<int, array<string, mixed>|object>|null */
    protected ?array $bufferData = null;

    /** @var int<0, max>|null */
    protected ?int $count = null;

    protected ResultInterface $dataSource;

    protected ?int $fieldCount = null;

    protected int $position = 0;

    public function __construct()
    {
        $this->dataSource = new EmptyResult();
    }

    /**
     * Set the data source for the result set
     *
     * @param iterable<array<string, mixed>|object> $dataSource
     * @throws InvalidArgumentException|Exception
     */
    #[Override]
    public function initialize(iterable $dataSource): ResultSetInterface
    {
        if ($this->bufferState === BufferState::Active) {
            $this->bufferData = [];
        }

        if ($dataSource instanceof ResultInterface) {
            $this->fieldCount = $dataSource->getFieldCount();
            $this->dataSource = $dataSource;
            if ($dataSource->isBuffered()) {
                $this->bufferState = BufferState::DataSourceBuffered;
            }

            if ($this->bufferState === BufferState::Active) {
                $this->dataSource->rewind();
            }

            return $this;
        }

        if (is_array($dataSource)) {
            $first = current($dataSource);
            reset($dataSource);
            if ($first === false) {
                $this->fieldCount = 0;
            } elseif (is_array($first) || $first instanceof Countable) {
                $this->fieldCount = count($first);
            } else {
                $this->fieldCount = count((array) $first);
            }
            $this->dataSource  = new ArrayResult($dataSource, $this->fieldCount);
            $this->bufferState = BufferState::DataSourceBuffered;
        } elseif ($dataSource instanceof IteratorAggregate || $dataSource instanceof Iterator) {
            $this->dataSource = new IteratorResult($dataSource);
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
        if ($this->bufferState === BufferState::Disabled) {
            throw new RuntimeException('Buffering must be enabled before iteration is started');
        } elseif ($this->bufferState === BufferState::None) {
            $this->bufferState = BufferState::Active;
            $this->bufferData  = [];
            $this->dataSource->rewind();
        }

        return $this;
    }

    public function isBuffered(): bool
    {
        return $this->bufferState === BufferState::DataSourceBuffered
            || $this->bufferState === BufferState::Active;
    }

    /**
     * Get the data source used to create the result set
     */
    public function getDataSource(): ResultInterface
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
        if ($this->bufferState === BufferState::None) {
            $this->bufferState = BufferState::Disabled;
        }

        if ($this->bufferState !== BufferState::Active || $this->position === $this->dataSource->key()) {
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
     *
     * @return array<string, mixed>|object|null
     */
    #[Override]
    public function current(): array|object|null
    {
        if ($this->bufferState === BufferState::DataSourceBuffered) {
            return $this->dataSource->current();
        }

        if ($this->bufferState === BufferState::None) {
            $this->bufferState = BufferState::Disabled;
        } elseif ($this->bufferState === BufferState::Active && isset($this->bufferData[$this->position])) {
            return $this->bufferData[$this->position];
        }

        $data = $this->dataSource->current();
        if ($this->bufferState === BufferState::Active && $this->bufferData !== null) {
            $this->bufferData[$this->position] = $data;
        }

        return is_array($data) ? $data : null;
    }

    /**
     * Iterator: is pointer valid?
     */
    #[Override]
    public function valid(): bool
    {
        if ($this->bufferState === BufferState::Active && isset($this->bufferData[$this->position])) {
            return true;
        }

        return $this->dataSource->valid();
    }

    /**
     * Iterator: rewind
     */
    #[Override]
    public function rewind(): void
    {
        if ($this->bufferState !== BufferState::Active) {
            $this->dataSource->rewind();
        }

        $this->position = 0;
    }

    /**
     * Countable: return count of rows
     *
     * @return int<0, max>|null
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function count(): ?int
    {
        if ($this->count !== null) {
            return $this->count;
        }

        $this->count = $this->dataSource->count();
        return $this->count;
    }

    /**
     * Cast result set to array of arrays
     *
     * @return array<int, array<string, mixed>>
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

            /** @phpstan-ignore identical.alwaysFalse */
            if ($row === null) {
                continue;
            }

            if (
                ! method_exists($row, 'toArray')
                && ! method_exists($row, 'getArrayCopy')
            ) {
                throw new RuntimeException(
                    'Rows as part of this DataSource, with type ' . gettype($row) . ' cannot be cast to an array'
                );
            }

            /** @phpstan-ignore method.notFound */
            $return[] = method_exists($row, 'toArray') ? $row->toArray() : $row->getArrayCopy();
        }

        return $return;
    }
}
