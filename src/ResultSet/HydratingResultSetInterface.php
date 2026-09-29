<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

/**
 * Capability interface for a ResultSet whose rows are hydrated onto an arbitrary object prototype.
 *
 * @api
 */
interface HydratingResultSetInterface extends ResultSetInterface
{
    public function getRowPrototype(): object;

    public function setRowPrototype(object $rowPrototype): self;
}
