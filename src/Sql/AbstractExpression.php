<?php

declare(strict_types=1);

namespace PhpDb\Sql;

abstract class AbstractExpression implements ExpressionInterface
{
    protected ?string $specification = null;
    protected string $combination = 'AND';

    /**
     * Set the combination operator (AND/OR) for use in predicate sets
     */
    public function setCombination(string $combination): static
    {
        $this->combination = $combination;
        return $this;
    }

    /**
     * Get the combination operator
     */
    public function getCombination(): string
    {
        return $this->combination;
    }

    /**
     * Set specification string to override the default
     */
    public function setSpecification(string $specification): static
    {
        $this->specification = $specification;

        return $this;
    }

    /**
     * Get specification override, or null if not set
     */
    public function getSpecification(): ?string
    {
        return $this->specification;
    }
}
