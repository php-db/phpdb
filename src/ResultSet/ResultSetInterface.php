<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

use ArrayObject;
use Countable;
use Iterator;

interface ResultSetInterface extends Iterator, Countable
{
    /**
     * Can be anything iterable|array
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
     * @throws Exception\InvalidArgumentException
     */
    public function setRowPrototype(ArrayObject|RowPrototypeInterface $rowPrototype): ResultSetInterface;

    /**
     * Get the row object prototype
     */
    public function getRowPrototype(): ?object;
}
