<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\PreparableSqlInterface;

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

    public function getSpecification(): string
    {
        if (! str_contains($this->identifier, '.')) {
            return PreparableSqlInterface::P_LQUOTE . $this->identifier . PreparableSqlInterface::P_RQUOTE;
        }

        return PreparableSqlInterface::P_LQUOTE
            . str_replace(
                '.',
                PreparableSqlInterface::P_RQUOTE . '.' . PreparableSqlInterface::P_LQUOTE,
                $this->identifier
            )
            . PreparableSqlInterface::P_RQUOTE;
    }
}
