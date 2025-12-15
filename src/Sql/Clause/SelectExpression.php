<?php

declare(strict_types=1);

namespace PhpDb\Sql\Clause;

use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\PreparableSqlBuilder;
use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;

use function is_string;

final readonly class SelectExpression implements PreparableSqlInterface
{
    public const SQL_STAR = '*';

    public function __construct(
        public array $columns = [self::SQL_STAR],
        public bool $prefixWithTable = true
    ) {
    }

    /**
     * Build columns SQL part with quoted identifiers.
     */
    public function prepareSqlString(PreparableSqlBuilder $builder, ?TableIdentifier $table = null): string
    {
        $q      = $builder->q;
        $prefix = $this->prefixWithTable && $table !== null
            ? $q . $table->getReference() . $q . '.'
            : '';

        $result = '';
        $first  = true;

        foreach ($this->columns as $alias => $column) {
            if ($first) {
                $first = false;
            } else {
                $result .= ', ';
            }

            // Fast path: string column (most common)
            if (is_string($column)) {
                if ($column === self::SQL_STAR) {
                    $result .= $prefix . '*';
                    continue;
                }
                $columnSql = $prefix . $q . $column . $q;
            } elseif ($column instanceof ExpressionInterface) {
                $columnSql = $builder->processExpression($column);
            } elseif ($column instanceof Select) {
                $columnSql = '(' . $builder->processSubSelect($column) . ')';
            } else {
                continue;
            }

            // Add alias if present (string key)
            $result .= is_string($alias)
                ? $columnSql . ' AS ' . $q . $alias . $q
                : $columnSql;
        }

        return $result;
    }
}
