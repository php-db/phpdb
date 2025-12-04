<?php

declare(strict_types=1);

namespace PhpDb\Sql;

abstract class AbstractExpression implements ExpressionInterface
{
    protected ?string $specification = null;

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
