<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\PreparableSqlBuilder;

/**
 * Represents a raw SQL literal expression.
 * The value will be inserted directly into the SQL without escaping.
 * Use with caution - ensure the value is safe and does not contain
 * user-provided input.
 */
final readonly class Literal implements ArgumentInterface
{
    public function __construct(
        public string $value
    ) {
    }

    public function getType(): ArgumentType
    {
        return ArgumentType::Literal;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function toSql(PreparableSqlBuilder $builder): string
    {
        return $this->value;
    }
}
