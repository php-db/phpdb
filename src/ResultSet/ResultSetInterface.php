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
    public function getFieldCount(): mixed;

<<<<<<< HEAD
<<<<<<< HEAD
    /**
     * Set the row object prototype
     *
     * @throws Exception\InvalidArgumentException
     * @return $this Provides a fluent interface
     */
    public function setRowPrototype(ArrayObject $rowPrototype): ResultSetInterface;

    /**
     * Get the row object prototype
     */
    public function getRowPrototype(): ?object;
=======
    public function setObjectPrototype(ArrayObject $ObjectPrototype): ResultSetInterface;
>>>>>>> 2457778f (Add setObjectPrototype to ResultSetInterface)
=======
    public function setObjectPrototype(object $ObjectPrototype): ResultSetInterface;
>>>>>>> fde113e5 (Signed-off-by: Joey Smith <jsmith@webinertia.net>)
}
