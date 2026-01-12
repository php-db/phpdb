<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver;

use ArrayIterator;
use Override;

use function count;

/**
 * ResultInterface implementation that wraps an array
 *
 * Used when AbstractResultSet is initialized with an array data source,
 * allowing the dataSource property to be typed as just ResultInterface.
 *
 * @template TValue of array<string, mixed>|object
 */
final class ArrayResult implements ResultInterface
{
    /** @var ArrayIterator<int, TValue> */
    private ArrayIterator $iterator;

    /** @var int<0, max> */
    private int $count;

    private int $fieldCount;

    /**
     * @param array<int, TValue> $data
     */
    public function __construct(array $data, int $fieldCount = 0)
    {
        $this->iterator   = new ArrayIterator($data);
        $this->count      = count($data);
        $this->fieldCount = $fieldCount;
    }

    #[Override]
    public function buffer(): void
    {
        // Arrays are naturally buffered, nothing to do
    }

    #[Override]
    public function isBuffered(): ?bool
    {
        return true;
    }

    #[Override]
    public function isQueryResult(): bool
    {
        return true;
    }

    #[Override]
    public function getAffectedRows(): int
    {
        return 0;
    }

    #[Override]
    public function getGeneratedValue(): mixed
    {
        return null;
    }

    #[Override]
    public function getResource(): mixed
    {
        return $this->iterator->getArrayCopy();
    }

    #[Override]
    public function getFieldCount(): int
    {
        return $this->fieldCount;
    }

    /** @return TValue */
    #[Override]
    public function current(): mixed
    {
        return $this->iterator->current();
    }

    #[Override]
    public function key(): int
    {
        return $this->iterator->key();
    }

    #[Override]
    public function next(): void
    {
        $this->iterator->next();
    }

    #[Override]
    public function rewind(): void
    {
        $this->iterator->rewind();
    }

    #[Override]
    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    /** @return int<0, max> */
    #[Override]
    public function count(): int
    {
        return $this->count;
    }
}
