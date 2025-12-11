<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use function is_string;

class Columns
{
    public const SQL_STAR = '*';

    protected array $columns = [self::SQL_STAR];
    protected bool $prefixWithTable = true;

    public function __construct(array $columns = [self::SQL_STAR], bool $prefixWithTable = true)
    {
        $this->columns = $columns;
        $this->prefixWithTable = $prefixWithTable;
    }

    public function set(array $columns, bool $prefixWithTable = true): static
    {
        $this->columns = $columns;
        $this->prefixWithTable = $prefixWithTable;
        return $this;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function isPrefixWithTable(): bool
    {
        return $this->prefixWithTable;
    }

    /**
     * Build columns SQL part with marker-based identifiers.
     *
     * @param TableIdentifier|null $table The table for prefixing columns
     * @param callable|null $expressionProcessor Callback to process ExpressionInterface/Select objects
     */
    public function toSqlPart(
        ?TableIdentifier $table,
        ?callable $expressionProcessor = null
    ): string {
        $prefix = $this->prefixWithTable && $table !== null
            ? '{"' . $table->getRef() . '"}.'
            : '';

        $result = '';
        $first = true;

        foreach ($this->columns as $alias => $column) {
            if (!$first) {
                $result .= ', ';
            }
            $first = false;

            if ($column === self::SQL_STAR) {
                $result .= $prefix . '*';
            } elseif ($column instanceof ExpressionInterface) {
                $columnName = $expressionProcessor ? $expressionProcessor($column) : (string) $column;
                $result .= is_string($alias)
                    ? $columnName . ' AS {"' . $alias . '"}'
                    : $columnName;
            } elseif ($column instanceof Select) {
                $columnName = $expressionProcessor ? $expressionProcessor($column) : '{SQL}';
                $result .= is_string($alias)
                    ? '(' . $columnName . ') AS {"' . $alias . '"}'
                    : '(' . $columnName . ')';
            } elseif (is_string($alias)) {
                $result .= $prefix . '{"' . $column . '"} AS {"' . $alias . '"}';
            } else {
                $result .= $prefix . '{"' . $column . '"}';
            }
        }

        return $result;
    }

}