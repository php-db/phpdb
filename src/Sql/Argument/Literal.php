<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;

/**
 * Represents a raw SQL literal expression.
 * The value will be inserted directly into the SQL without escaping.
 * Use with caution - ensure the value is safe and does not contain
 * user-provided input.
 */
final readonly class Literal implements ArgumentInterface
{
    public function __construct(
        private string $literal
    ) {
    }

    public function getType(): ArgumentType
    {
        return ArgumentType::Literal;
    }

    public function getValue(): string
    {
        return $this->literal;
    }

    public function getSpecification(): string
    {
        return '%s';
    }
}
