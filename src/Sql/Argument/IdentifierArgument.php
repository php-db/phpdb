<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

/**
 * Represents a SQL identifier (table name, column name, alias, etc.).
 *
 * Identifiers will be quoted appropriately by the platform driver
 * to protect against reserved word conflicts.
 */
final readonly class IdentifierArgument implements ArgumentInterface
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
}
