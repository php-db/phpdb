<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

use ArrayObject;
use Countable;
use Iterator;

/**
 * @extends Iterator<int, array<string, mixed>|object>
 */
interface ResultSetInterface extends Iterator, Countable
{
    /**
     * Can be anything iterable|array
     *
     * @param iterable<array<string, mixed>|object> $dataSource
     */
    public function initialize(iterable $dataSource): ResultSetInterface;

    /**
     * Field terminology is more correct as information coming back
     * from the database might be a column, and/or the result of an
     * operation or intersection of some data
     */
    public function getFieldCount(): int;

    /**
     * Set the row object prototype
     *
     * @param ArrayObject<string, mixed>|RowPrototypeInterface $rowPrototype
     * @throws Exception\InvalidArgumentException
     */
    public function setRowPrototype(ArrayObject|RowPrototypeInterface $rowPrototype): ResultSetInterface;

    /**
     * Get the row object prototype
     */
    public function getRowPrototype(): ?object;
}
