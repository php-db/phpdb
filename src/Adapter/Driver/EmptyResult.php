<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver;

use Override;

/**
 * Null Object implementation of ResultInterface
 *
 * Used as the default data source in AbstractResultSet before initialization,
 * eliminating the need for null checks throughout the codebase.
 */
final class EmptyResult implements ResultInterface
{
    #[Override]
    public function buffer(): void
    {
    }

    #[Override]
    public function isBuffered(): ?bool
    {
        return true;
    }

    #[Override]
    public function isQueryResult(): bool
    {
        return false;
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
        return null;
    }

    #[Override]
    public function getFieldCount(): int
    {
        return 0;
    }

    #[Override]
    public function current(): mixed
    {
        return null;
    }

    #[Override]
    public function key(): mixed
    {
        return null;
    }

    #[Override]
    public function next(): void
    {
    }

    #[Override]
    public function rewind(): void
    {
    }

    #[Override]
    public function valid(): bool
    {
        return false;
    }

    #[Override]
    public function count(): int
    {
        return 0;
    }
}
