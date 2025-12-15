<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\PreparableSqlBuilder;

use function array_values;

/**
 * Represents multiple bound parameter values in SQL.
 *
 * Used for IN clauses and similar constructs where multiple values
 * are needed as bound parameters.
 */
final readonly class Values implements ArgumentInterface
{
    /** @var list<null|string|int|float|bool> */
    private array $values;

    /**
     * @param list<null|string|int|float|bool> $values
     */
    public function __construct(array $values)
    {
        $this->values = array_values($values);
    }

    public function getType(): ArgumentType
    {
        return ArgumentType::Values;
    }

    /**
     * @return list<null|string|int|float|bool>
     */
    public function getValue(): array
    {
        return $this->values;
    }

    public function toSql(PreparableSqlBuilder $builder): string
    {
        return $this->values === []
            ? '(NULL)'
            : '(' . $builder->bindValues($this->values) . ')';
    }
}
