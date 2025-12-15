<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\PreparableSqlBuilder;

/**
 * Represents a SQL identifier (table name, column name, alias, etc.).
 * Identifiers will be quoted appropriately by the platform driver
 * to protect against reserved word conflicts.
 */
final readonly class Identifier implements ArgumentInterface
{
    public function __construct(
        public string $value
    ) {
    }

    public function getType(): ArgumentType
    {
        return ArgumentType::Identifier;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function toSql(PreparableSqlBuilder $builder): string
    {
        return PreparableSqlBuilder::quoteId($this->value, $builder->q);
    }
}
