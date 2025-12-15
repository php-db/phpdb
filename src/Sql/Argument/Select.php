<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\PreparableSqlBuilder;
use PhpDb\Sql\Select as SelectStatement;
use PhpDb\Sql\SqlInterface;

/**
 * Represents a subquery or expression in SQL.
 * Used when embedding a SELECT statement or expression as part of
 * another query (e.g., subqueries, derived tables).
 */
final readonly class Select implements ArgumentInterface
{
    public function __construct(
        private ExpressionInterface|SqlInterface $select
    ) {
    }

    public function getType(): ArgumentType
    {
        return ArgumentType::Select;
    }

    public function getValue(): ExpressionInterface|SqlInterface
    {
        return $this->select;
    }

    public function toSql(PreparableSqlBuilder $builder): string
    {
        return $this->select instanceof SelectStatement
            ? $builder->processSubSelect($this->select)
            : $builder->processExpression($this->select);
    }
}
