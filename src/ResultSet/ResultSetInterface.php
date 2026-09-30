<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

use Countable;
use Iterator;

/**
 * @api
 *
 * @template TRow
 *
 * @extends Iterator<int, TRow>
 */
interface ResultSetInterface extends Iterator, Countable
{
    /**
     * Field terminology is more correct as information coming back
     * from the database might be a column, and/or the result of an
     * operation or intersection of some data
     */
    public function getFieldCount(): int;

    /**
     * Can be anything iterable|array
     *
     * @param iterable<array-key, mixed> $dataSource
     */
    public function initialize(iterable $dataSource): self;

    /**
     * Get all rows as an array
     *
     * @return list<mixed>
     */
    public function toArray(): array;
}
