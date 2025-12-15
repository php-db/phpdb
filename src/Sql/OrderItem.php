<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use function str_contains;
use function str_replace;

final readonly class OrderItem
{
    public function __construct(
        public string $column,
        public string $direction = 'ASC',
        public bool $isExpression = false
    ) {
    }

    public static function create(string $column, string $direction = 'ASC'): self
    {
        return new self($column, $direction, false);
    }

    public static function fromExpression(ExpressionInterface $expr): self
    {
        return new self($expr->getExpressionData()['spec'], '', true);
    }

    /**
     * Build SQL for this order item.
     *
     * @param string $q Quote character (empty string = no quoting)
     */
    public function toSql(string $q): string
    {
        if ($this->isExpression) {
            return $this->column;
        }

        $quoted = str_contains($this->column, '.')
            ? $q . str_replace('.', $q . '.' . $q, $this->column) . $q
            : $q . $this->column . $q;

        return $quoted . ' ' . $this->direction;
    }
}
