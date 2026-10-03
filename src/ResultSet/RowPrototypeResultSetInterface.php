<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

/**
 * Capability interface for a ResultSet whose rows clone a RowPrototypeInterface prototype.
 *
 * @api
 */
interface RowPrototypeResultSetInterface
{
    public function getRowPrototype(): RowPrototypeInterface;

    public function setRowPrototype(RowPrototypeInterface $rowPrototype): ResultSetInterface&RowPrototypeResultSetInterface;
}
