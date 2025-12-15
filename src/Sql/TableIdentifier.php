<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use function current;
use function explode;
use function is_array;
use function key;
use function str_contains;
use function trigger_error;

use const E_USER_DEPRECATED;

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
final readonly class TableIdentifier implements PreparableSqlInterface
{
    /**
     * Create a TableIdentifier from various input formats.
     *
     * @param string|array|self $table Table name, ['alias' => 'table'] array, or existing TableIdentifier
     * @param string|null $alias Optional alias (ignored if $table is array with alias)
     * @param bool $readOnly Whether this table reference is read-only (cannot be changed)
     */
    public static function from(string|array|self $table, ?string $alias = null, bool $readOnly = false): self
    {
        if ($table instanceof self) {
            $needsNew = ($alias !== null && $alias !== $table->alias) || ($readOnly && ! $table->readOnly);
            return $needsNew
                ? new self($table->table, $table->schema, $alias ?? $table->alias, $readOnly || $table->readOnly)
                : $table;
        }

        if (is_array($table)) {
            $alias = (string) key($table);
            $table = (string) current($table);
        }

        if (str_contains($table, '.')) {
            $parts = explode('.', $table, 2);
            return new self($parts[1], $parts[0], $alias, $readOnly);
        }

        return new self($table, null, $alias, $readOnly);
    }

    public function __construct(
        protected string $table,
        protected ?string $schema = null,
        protected ?string $alias = null,
        protected bool $readOnly = false
    ) {
        if ($table === '') {
            throw new Exception\InvalidArgumentException('$table must be a valid table name, empty string given');
        }
        if ($schema === '') {
            throw new Exception\InvalidArgumentException(
                '$schema must be a valid schema name or null, empty string given'
            );
        }
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly;
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
     * Get the reference name for this table (alias if set, otherwise full qualified name).
     * Used for column prefixing like "users.id", "u.id", or "schema.table.id".
     */
    public function getReference(): string
    {
        if ($this->alias !== null) {
            return $this->alias;
        }

        return $this->schema !== null
            ? $this->schema . '"."' . $this->table
            : $this->table;
    }

    /**
     * String representation for debugging and test output.
     */
    public function __toString(): string
    {
        $result = $this->schema !== null ? $this->schema . '.' . $this->table : $this->table;
        return $this->alias !== null ? $result . ' AS ' . $this->alias : $result;
    }

    /**
     * Create a new TableIdentifier with an alias.
     */
    public function as(string $alias): self
    {
        return new self($this->table, $this->schema, $alias, $this->readOnly);
    }

    /**
     * @deprecated Use getTable() and getSchema() or prepareSqlString() instead
     *
     * @return array{0: string, 1: null|string}
     */
    public function getTableAndSchema(): array
    {
        return [$this->table, $this->schema];
    }

    /**
     * Set the table name.
     *
     * @deprecated TableIdentifier is now immutable. Create a new instance instead.
     *
     * @return self Returns a NEW instance with the updated table name
     */
    public function setTable(string $table): self
    {
        trigger_error(
            'TableIdentifier::setTable() is deprecated. '
            . 'TableIdentifier is now immutable. Create a new instance instead.',
            E_USER_DEPRECATED
        );

        return new self($table, $this->schema, $this->alias, $this->readOnly);
    }

    /**
     * Set the schema name.
     *
     * @deprecated TableIdentifier is now immutable. Create a new instance instead.
     *
     * @return self Returns a NEW instance with the updated schema
     */
    public function setSchema(?string $schema): self
    {
        trigger_error(
            'TableIdentifier::setSchema() is deprecated. '
            . 'TableIdentifier is now immutable. Create a new instance instead.',
            E_USER_DEPRECATED
        );

        return new self($this->table, $schema, $this->alias, $this->readOnly);
    }

    /**
     * Check if a schema is set.
     *
     * @deprecated Use getSchema() !== null instead
     */
    public function hasSchema(): bool
    {
        trigger_error(
            'TableIdentifier::hasSchema() is deprecated. Use getSchema() !== null instead.',
            E_USER_DEPRECATED
        );

        return $this->schema !== null;
    }

    /**
     * Generate the SQL part with quoted identifiers.
     */
    public function prepareSqlString(PreparableSqlBuilder $builder): string
    {
        $q   = $builder->q;
        $sql = $this->schema !== null
            ? $q . $this->schema . $q . '.' . $q . $this->table . $q
            : $q . $this->table . $q;

        if ($this->alias !== null) {
            $sql .= ' AS ' . $q . $this->alias . $q;
        }

        return $sql;
    }

    /**
     * Generate SQL for FROM clause (includes " FROM " prefix).
     */
    public function toFromSqlPart(PreparableSqlBuilder $builder): string
    {
        return ' FROM ' . $this->prepareSqlString($builder);
    }
}
