<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;

use function current;
use function is_array;
use function is_string;
use function key;

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
     * @param string|array|TableIdentifier|null $table The table for prefixing columns
     * @param Join|null $joins Join object to include join columns
     * @param callable|null $expressionProcessor Callback to process ExpressionInterface objects
     */
    public function toSqlPart(
        string|array|TableIdentifier|null $table,
        ?Join $joins = null,
        ?callable $expressionProcessor = null
    ): string {
        // Build table prefix
        $tableRef = $this->getTableRef($table);
        $prefix = $this->prefixWithTable && $tableRef ? '{"' . $tableRef . '"}.' : '';

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
            } elseif (is_string($alias)) {
                $result .= $prefix . '{"' . $column . '"} AS {"' . $alias . '"}';
            } else {
                $result .= $prefix . '{"' . $column . '"}';
            }
        }

        // Join columns
        if ($joins !== null) {
            foreach ($joins->getJoins() as $join) {
                $joinTable = is_array($join['name']) ? key($join['name']) : $join['name'];
                $joinPrefix = '{"' . $joinTable . '"}.';

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

    /**
     * Get the table reference name for column prefixing
     */
    protected function getTableRef(string|array|TableIdentifier|null $table): ?string
    {
        if ($table === null) {
            return null;
        }

        if (is_array($table)) {
            return (string) key($table);
        }

        if ($table instanceof TableIdentifier) {
            return $table->getTable();
        }

        return $table;
    }
}