<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;

use function str_contains;
use function str_replace;

/**
 * Represents a SQL identifier (table name, column name, alias, etc.).
 * Identifiers will be quoted appropriately by the platform driver
 * to protect against reserved word conflicts.
 */
final readonly class Identifier implements ArgumentInterface
{
    public function __construct(
        private string $identifier
    ) {
    }

    public function getType(): ArgumentType
    {
        return ArgumentType::Identifier;
    }

    public function getValue(): string
    {
        return $this->identifier;
    }

    /**
     * Get raw identifier (no quoting).
     */
    public function getSpecification(): string
    {
        return $this->identifier;
    }

    /**
     * Get the quoted identifier SQL.
     *
     * @param string $q Quote character (empty string = no quoting)
     */
    public function toSql(string $q): string
    {
        if (! str_contains($this->identifier, '.')) {
            return $q . $this->identifier . $q;
        }

        return $q . str_replace('.', $q . '.' . $q, $this->identifier) . $q;
    }
}
