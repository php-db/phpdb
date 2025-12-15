<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\PreparableSqlBuilder;

/**
 * Represents a bound parameter value in SQL.
 * Used for values that will be sent as bound parameters to the database,
 * providing protection against SQL injection.
 */
final readonly class Value implements ArgumentInterface
{
    /**
     * @param null|string|int|float|bool $value Scalar value, or null
     */
    public function __construct(
        public null|string|int|float|bool $value
    ) {
    }

    public function getType(): ArgumentType
    {
        return ArgumentType::Value;
    }

    public function getValue(): null|string|int|float|bool
    {
        return $this->value;
    }

    public function toSql(PreparableSqlBuilder $builder): string
    {
        return $builder->bindValue($this->value);
    }
}
