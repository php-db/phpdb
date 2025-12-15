<?php

declare(strict_types=1);

namespace PhpDb\Sql\Clause;

use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\PreparableSqlBuilder;

final readonly class OrderSpecification
{
    public function __construct(
        public string $column,
        public string $direction = 'ASC',
        public bool $isExpression = false,
        public ?ExpressionInterface $expression = null
    ) {
    }

    public static function create(string $column, string $direction = 'ASC'): self
    {
        return new self($column, $direction, false);
    }

    public static function fromExpression(ExpressionInterface $expr): self
    {
        return new self('', '', true, $expr);
    }

    /**
     * Build SQL for this order item.
     */
    public function toSql(PreparableSqlBuilder $builder): string
    {
        if ($this->isExpression && $this->expression !== null) {
            return $builder->processExpression($this->expression);
        }

        if ($this->isExpression) {
            // Fallback for expressions without a builder - should not happen in practice
            return $this->column;
        }

        return PreparableSqlBuilder::quoteId($this->column, $builder->q) . ' ' . $this->direction;
    }
}
