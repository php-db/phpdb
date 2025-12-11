<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use function current;
use function explode;
use function is_array;
use function key;
use function str_contains;

/**
 * Represents a table reference with optional schema and alias.
 *
 * This is the canonical way to represent tables throughout the SQL builder.
 * Use the static factory methods for convenience:
 *
 *   TableIdentifier::from('users')              // Simple table
 *   TableIdentifier::from('users', 'u')         // Table with alias
 *   TableIdentifier::from(['u' => 'users'])     // Array syntax for alias
 *   TableIdentifier::from('public.users')       // Schema.table (parsed)
 *   new TableIdentifier('users', 'public', 'u') // Full constructor
 */
class TableIdentifier
{
    /**
     * Create a TableIdentifier from various input formats.
     *
     * @param string|array|self $table Table name, ['alias' => 'table'] array, or existing TableIdentifier
     * @param string|null $alias Optional alias (ignored if $table is array with alias)
     */
    public static function from(string|array|self $table, ?string $alias = null): self
    {
        if ($table instanceof self) {
            // If alias provided, create new instance with that alias
            return $alias !== null && $alias !== $table->alias
                ? new self($table->table, $table->schema, $alias)
                : $table;
        }

        if (is_array($table)) {
            $alias = (string) key($table);
            $table = (string) current($table);
        }

        // Check for schema.table format
        if (str_contains($table, '.')) {
            $parts = explode('.', $table, 2);
            return new self($parts[1], $parts[0], $alias);
        }

        return new self($table, null, $alias);
    }

    public function __construct(
        protected readonly string $table,
        protected readonly ?string $schema = null,
        protected readonly ?string $alias = null
    ) {
        if ($table === '') {
            throw new Exception\InvalidArgumentException('$table must be a valid table name, empty string given');
        }
        if ($schema === '') {
            throw new Exception\InvalidArgumentException('$schema must be a valid schema name or null, empty string given');
        }
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getSchema(): ?string
    {
        return $this->schema;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * Get the reference name for this table (alias if set, otherwise table name).
     * Used for column prefixing like "users.id" or "u.id".
     */
    public function getRef(): string
    {
        return $this->alias ?? $this->table;
    }

    /**
     * Create a new TableIdentifier with an alias.
     */
    public function as(string $alias): self
    {
        return new self($this->table, $this->schema, $alias);
    }

    /**
     * @deprecated Use getTable() and getSchema() or toSqlPart() instead
     * @return array{0: string, 1: null|string}
     */
    public function getTableAndSchema(): array
    {
        return [$this->table, $this->schema];
    }

    /**
     * Generate the SQL part with marker-based identifiers.
     *
     * Examples:
     *   {"users"}
     *   {"public"}.{"users"}
     *   {"users"} AS {"u"}
     *   {"public"}.{"users"} AS {"u"}
     */
    public function toSqlPart(): string
    {
        $sql = $this->schema !== null
            ? '{"' . $this->schema . '"}.{"' . $this->table . '"}'
            : '{"' . $this->table . '"}';

        if ($this->alias !== null) {
            $sql .= ' AS {"' . $this->alias . '"}';
        }

        return $sql;
    }

    /**
     * Generate SQL for FROM clause (includes " FROM " prefix).
     */
    public function toFromSqlPart(): string
    {
        return ' FROM ' . $this->toSqlPart();
    }
}
