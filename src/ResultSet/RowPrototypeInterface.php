<?php

declare(strict_types=1);

namespace PhpDb\ResultSet;

/**
 * Interface for objects that can serve as row prototypes in ResultSets.
 *
 * Row prototypes are cloned for each row and populated via exchangeArray().
 * This interface allows custom row objects (like RowGateway) to be used
 * as prototypes alongside ArrayObject.
 */
interface RowPrototypeInterface
{
    /**
     * Exchange the current data for the provided array.
     *
     * @param array<string, mixed> $array
     * @return array<string, mixed>
     */
    public function exchangeArray(array $array): array;
}
