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
 * Represents multiple SQL identifiers (column names for multi-column clauses).
 *
 * Used for multi-column IN predicates like (col1, col2) IN (SELECT ...).
 * Each identifier will be quoted appropriately by the platform driver.
 */
final readonly class Identifiers implements ArgumentInterface
{
    /** @var list<string> */
    private array $identifiers;

    /**
     * @param list<string> $identifiers
     */
    public function __construct(array $identifiers)
    {
        $this->identifiers = array_values($identifiers);
    }

    public function getType(): ArgumentType
    {
        return ArgumentType::Identifiers;
    }

    /**
     * @return list<string>
     */
    public function getValue(): array
    {
        return $this->identifiers;
    }

    public function getSpecification(): string
    {
        $count = count($this->identifiers);
        return $count > 0
            ? '(' . implode(', ', array_fill(0, $count, '%s')) . ')'
            : '(NULL)';
    }
}
