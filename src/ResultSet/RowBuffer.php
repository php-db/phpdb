<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

use PhpDb\ResultSet\Exception\RuntimeException;

use function array_key_exists;

/**
 * The rows a result set holds on to, and whether it holds any at all.
 *
 * A result set owns one of these for its lifetime. It replaces the overloaded sentinel
 * the buffer state used to be encoded in, so that a state the class does not define
 * cannot be reached.
 *
 * @internal
 *
 * @template TRow
 */
final class RowBuffer
{
    /** @var array<int, TRow> */
    private array $rows = [];

    private RowBufferState $state = RowBufferState::Pending;

    /**
     * Forget every row held, leaving the state alone.
     */
    public function clear(): void
    {
        $this->rows = [];
    }

    /**
     * The row held for a position, if one is held there.
     *
     * @return TRow|null
     */
    public function get(int $position): mixed
    {
        return $this->rows[$position] ?? null;
    }

    /**
     * Whether a row is held for a position.
     */
    public function has(int $position): bool
    {
        return RowBufferState::Storing === $this->state && array_key_exists($position, $this->rows);
    }

    /**
     * Whether rows can be read more than once, however that came about.
     */
    public function isBuffered(): bool
    {
        return RowBufferState::Storing === $this->state || RowBufferState::Passthrough === $this->state;
    }

    /**
     * Whether buffering has still to be settled either way.
     */
    public function isPending(): bool
    {
        return RowBufferState::Pending === $this->state;
    }

    /**
     * Whether rows are being held here as they are read.
     */
    public function isStoring(): bool
    {
        return RowBufferState::Storing === $this->state;
    }

    /**
     * Hold a row for a position, if rows are being held at all.
     *
     * @param TRow $row
     */
    public function put(int $position, mixed $row): void
    {
        if (! $this->isStoring()) {
            return;
        }

        $this->rows[$position] = $row;
    }

    /**
     * Settle an undecided buffer, because iteration has begun and buffer() is too late.
     */
    public function startIteration(): void
    {
        if (! $this->isPending()) {
            return;
        }

        $this->state = RowBufferState::Disabled;
    }

    /**
     * Begin holding rows.
     *
     * @throws RuntimeException If iteration has already settled the buffer.
     */
    public function store(): void
    {
        if (RowBufferState::Disabled === $this->state) {
            throw RuntimeException::forUnbufferedIteration();
        }

        if ($this->isBuffered()) {
            return;
        }

        $this->state = RowBufferState::Storing;
        $this->rows  = [];
    }

    /**
     * Defer to a data source that can already be read more than once.
     */
    public function useDataSource(): void
    {
        $this->state = RowBufferState::Passthrough;
        $this->rows  = [];
    }
}
