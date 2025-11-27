<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\SqlInterface;

/**
 * Factory for creating argument instances.
 */
final class Argument
{
    public static function value(null|string|int|float|bool $value): ValueArgument
    {
        return new ValueArgument($value);
    }

    public static function values(array $values): ValuesArgument
    {
        return new ValuesArgument($values);
    }

    public static function identifier(string $identifier): IdentifierArgument
    {
        return new IdentifierArgument($identifier);
    }

    /**
     * @param list<string> $identifiers
     */
    public static function identifiers(array $identifiers): IdentifiersArgument
    {
        return new IdentifiersArgument($identifiers);
    }

    public static function literal(string $literal): LiteralArgument
    {
        return new LiteralArgument($literal);
    }

    public static function select(ExpressionInterface|SqlInterface $select): SelectArgument
    {
        return new SelectArgument($select);
    }
}
