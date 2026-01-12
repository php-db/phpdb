<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver;

use Countable;
use Iterator;
use IteratorAggregate;
use Override;

/**
 * ResultInterface implementation that wraps an Iterator or IteratorAggregate
 *
 * Used when AbstractResultSet is initialized with an iterator data source,
 * allowing the dataSource property to be typed as just ResultInterface.
 *
 * @template TKey
 * @template TValue of array<string, mixed>|object
 */
final class IteratorResult implements ResultInterface
{
    /** @var Iterator<TKey, TValue> */
    private Iterator $iterator;

    /**
     * @param Iterator<TKey, TValue>|IteratorAggregate<TKey, TValue> $iterator
     */
    public function __construct(Iterator|IteratorAggregate $iterator)
    {
        if ($iterator instanceof IteratorAggregate) {
            /** @var Iterator<TKey, TValue> $innerIterator */
            $innerIterator  = $iterator->getIterator();
            $this->iterator = $innerIterator;
        } else {
            $this->iterator = $iterator;
        }
    }

    #[Override]
    public function buffer(): void
    {
        // Cannot buffer a generic iterator
    }

    #[Override]
    public function isBuffered(): ?bool
    {
        return false;
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
        return $this->iterator;
    }

    #[Override]
    public function getFieldCount(): int
    {
        return 0;
    }

    /** @return TValue */
    #[Override]
    public function current(): mixed
    {
        return $this->iterator->current();
    }

    #[Override]
    public function key(): mixed
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

    /**
     * @return int<0, max>
     */
    #[Override]
    public function count(): int
    {
        if ($this->iterator instanceof Countable) {
            return $this->iterator->count();
        }

        return 0;
    }
}
