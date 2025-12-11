<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use PhpDb\Sql\ExpressionInterface;

interface PredicateInterface extends ExpressionInterface
{
    /**
     * Build the SQL part string with markers for deferred quoting.
     *
     * Returns SQL with {"identifier"} markers for identifiers and {?} placeholders
     * for values. Values are appended to the provided array for later assembly.
     *
     * @param array<int, mixed> $values Values array to append to (passed by reference)
     * @return string SQL fragment with markers
     */
    public function toSqlPart(array &$values): string;
}
