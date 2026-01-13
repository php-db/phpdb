<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\PreparableSqlInterface;

interface PredicateInterface extends ExpressionInterface, PreparableSqlInterface
{
    /**
     * Set the combination operator (AND/OR) for use in predicate sets
     */
    public function setCombination(string $combination): static;

    /**
     * Get the combination operator
     */
    public function getCombination(): string;

    public function hasParentheses(): bool;
}
