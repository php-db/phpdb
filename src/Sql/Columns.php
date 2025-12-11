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
     * @param Join|null $joins Join object to include join columns
     * @param callable|null $expressionProcessor Callback to process ExpressionInterface objects
     */
    public function toSqlPart(
        ?TableIdentifier $table,
        ?Join $joins = null,
        ?callable $expressionProcessor = null
    ): string {
        // Build table prefix using TableIdentifier's getRef() method
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
                // Subquery column - use expression processor if available
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

        // Join columns
        if ($joins !== null) {
            foreach ($joins->getJoins() as $join) {
                /** @var TableIdentifier $joinTableId */
                $joinTableId = $join['name'];
                $joinPrefix = '{"' . $joinTableId->getRef() . '"}.';

                foreach ($join['columns'] as $jAlias => $jColumn) {
                    if (!$first) {
                        $result .= ', ';
                    }
                    $first = false;

                    if ($jColumn === self::SQL_STAR) {
                        $result .= $joinPrefix . '*';
                    } elseif ($jColumn instanceof ExpressionInterface) {
                        $columnName = $expressionProcessor ? $expressionProcessor($jColumn) : (string) $jColumn;
                        $result .= is_string($jAlias)
                            ? $columnName . ' AS {"' . $jAlias . '"}'
                            : $columnName;
                    } elseif (is_string($jAlias)) {
                        $result .= $joinPrefix . '{"' . $jColumn . '"} AS {"' . $jAlias . '"}';
                    } else {
                        $result .= $joinPrefix . '{"' . $jColumn . '"} AS {"' . $jColumn . '"}';
                    }
                }
            }
        }

        return $result;
    }

}