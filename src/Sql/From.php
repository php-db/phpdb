<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use function current;
use function is_array;
use function key;

final class From
{
    protected string|array|TableIdentifier $table;
    protected ?string $alias = null;

    public function __construct(string|array|TableIdentifier $table)
    {
        if (is_array($table)) {
            $this->alias = (string) key($table);
            $this->table = (string) current($table);
        } else {
            $this->table = $table;
        }
    }

    public function getTable(): string|TableIdentifier
    {
        return $this->table;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * Get the table reference name (alias if set, otherwise table name)
     */
    public function getTableRef(): string
    {
        if ($this->alias !== null) {
            return $this->alias;
        }

        if ($this->table instanceof TableIdentifier) {
            return $this->table->getTable();
        }

        return $this->table;
    }

    /**
     * Build FROM clause with marker-based identifiers
     */
    public function toSqlPart(): string
    {
        if ($this->table instanceof TableIdentifier) {
            [$tableName, $schema] = $this->table->getTableAndSchema();
            $sql = $schema
                ? ' FROM {"' . $schema . '"}.{"' . $tableName . '"}'
                : ' FROM {"' . $tableName . '"}';
        } else {
            $sql = ' FROM {"' . $this->table . '"}';
        }

        if ($this->alias !== null) {
            $sql .= ' AS {"' . $this->alias . '"}';
        }

        return $sql;
    }
}
