<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

/**
 * Interface for objects that can serve as row prototypes in RowPrototypeResultSets.
 *
 * Row prototypes are cloned (but do not have to be) for each row and populated via populate().
 * This interface allows custom row objects (like RowGateway) to be used
 * as prototypes without depending on ArrayObject.
 *
 * @api
 */
interface RowPrototypeInterface
{
    /**
     * Populate the prototype with row data. Mutating vs. returning a new instance is up to the implementation.
     */
    public function populate(array $data): self;

    /**
     * Current data as an array and match current RowGateway implementations.
     */
    public function toArray(): array;
}
