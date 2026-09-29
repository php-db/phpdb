<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

use ArrayObject;

/**
 * Capability interface for a ResultSet whose rows clone an ArrayObject prototype.
 *
 * @api
 */
interface ArrayObjectResultSetInterface extends ResultSetInterface
{
    public function getRowPrototype(): ArrayObject;

    public function setRowPrototype(ArrayObject $rowPrototype): self;
}
