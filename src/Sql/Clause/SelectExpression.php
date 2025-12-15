<?php

declare(strict_types=1);

namespace PhpDb\Sql\Clause;

use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;

use function is_string;

final readonly class SelectExpression
{
    public const SQL_STAR = '*';

    public function __construct(
        public array $columns = [self::SQL_STAR],
        public bool $prefixWithTable = true
    ) {
    }

    /**
     * Build columns SQL part with quoted identifiers.
     *
     * @param string $q Quote character (empty string = no quoting)
     * @param TableIdentifier|null $table The table for prefixing columns
     * @param callable|null $expressionProcessor Callback to process ExpressionInterface/Select objects
     */
    public function prepareSqlString(
        string $q,
        ?TableIdentifier $table,
        ?callable $expressionProcessor = null
    ): string {
        $prefix = $this->prefixWithTable && $table !== null
            ? $q . $table->getRef() . $q . '.'
            : '';

        $result = '';
        $first  = true;

        foreach ($this->columns as $alias => $column) {
            if (! $first) {
                $result .= ', ';
            }
            $first = false;

            // Handle star separately - no alias support
            if ($column === self::SQL_STAR) {
                $result .= $prefix . $column;
                continue;
            }

            // Resolve column to SQL string
            if ($column instanceof ExpressionInterface) {
                $columnSql = $expressionProcessor
                ? $expressionProcessor($column)
                : $column->getExpressionData()['spec'];
            } elseif ($column instanceof Select) {
                $columnSql = '(' . ($expressionProcessor ? $expressionProcessor($column) : '') . ')';
            } else {
                $columnSql = $prefix . $q . $column . $q;
            }

            // Apply alias once
            $result .= is_string($alias)
                ? $columnSql . ' AS ' . $q . $alias . $q
                : $columnSql;
        }

        return $result;
    }
}
