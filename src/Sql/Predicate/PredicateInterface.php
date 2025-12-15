<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\ExpressionInterface;

interface PredicateInterface extends ExpressionInterface
{
    /**
     * Build the SQL part string with quoted identifiers and values.
     *
     * @param string $q Quote character for identifiers (empty string = no quoting)
     * @param PlatformInterface $platform Platform for quoting values
     * @return string SQL fragment
     */
    public function prepareSqlString(string $q, PlatformInterface $platform): string;

    /**
     * Set the combination operator (AND/OR) for use in predicate sets
     */
    public function setCombination(string $combination): static;

    /**
     * Get the combination operator
     */
    public function getCombination(): string;
}
