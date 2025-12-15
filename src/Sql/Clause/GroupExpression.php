<?php

declare(strict_types=1);

namespace PhpDb\Sql\Clause;

use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\PreparableSqlBuilder;

final readonly class GroupExpression
{
    public function __construct(
        public string|ExpressionInterface $column
    ) {
    }

    /**
     * Build SQL for this group column.
     */
    public function toSql(PreparableSqlBuilder $builder): string
    {
        if ($this->column instanceof ExpressionInterface) {
            return $builder->processExpression($this->column);
        }

        return PreparableSqlBuilder::quoteId($this->column, $builder->q);
    }
}
