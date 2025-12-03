<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;

use function array_fill;
use function array_values;
use function count;
use function implode;

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

    public function getSpecification(): string
    {
        $count = count($this->values);
        return $count > 0
            ? '(' . implode(', ', array_fill(0, $count, '%s')) . ')'
            : '(NULL)';
    }
}
