<?php

declare(strict_types=1);

namespace PhpDb\Sql\Clause;

use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\PreparableSqlBuilder;

use function str_contains;
use function str_replace;

final readonly class GroupExpression
{
    public function __construct(
        public string|ExpressionInterface $column
    ) {
    }

    /**
     * Build SQL for this group column.
     */
    public function toSql(string $q, ?PreparableSqlBuilder $builder = null): string
    {
        if ($this->column instanceof ExpressionInterface) {
            return $builder !== null
                ? $builder->processExpression($this->column)
                : (string) $this->column;
        }

        return str_contains($this->column, '.')
            ? $q . str_replace('.', $q . '.' . $q, $this->column) . $q
            : $q . $this->column . $q;
    }
}
