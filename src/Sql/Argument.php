<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Identifiers;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Argument\Select;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\Argument\Values;

/**
 * Factory for creating argument instances.
 */
final class Argument
{
    public static function value(null|string|int|float|bool $value): Value
    {
        return new Value($value);
    }

    public static function values(array $values): Values
    {
        return new Values($values);
    }

    public static function identifier(string $identifier): Identifier
    {
        return new Identifier($identifier);
    }

    /**
     * @param list<string> $identifiers
     */
    public static function identifiers(array $identifiers): Identifiers
    {
        return new Identifiers($identifiers);
    }

    public static function literal(string $literal): Literal
    {
        return new Literal($literal);
    }

    public static function select(ExpressionInterface|SqlInterface $select): Select
    {
        return new Select($select);
    }
}
