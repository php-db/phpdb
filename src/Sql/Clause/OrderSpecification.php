<?php

declare(strict_types=1);

namespace PhpDb\Sql\Clause;

use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\PreparableSqlBuilder;

use function str_contains;
use function str_replace;

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
     *
     * @param string $q Quote character (empty string = no quoting)
     */
    public function toSql(string $q, ?PreparableSqlBuilder $builder = null): string
    {
        if ($this->isExpression && $this->expression !== null && $builder !== null) {
            return $builder->processExpression($this->expression);
        }

        if ($this->isExpression) {
            // Fallback for when no builder available - should not happen in practice
            return $this->column;
        }

        $quoted = str_contains($this->column, '.')
            ? $q . str_replace('.', $q . '.' . $q, $this->column) . $q
            : $q . $this->column . $q;

        return $quoted . ' ' . $this->direction;
    }
}
