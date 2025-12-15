<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\PreparableSqlBuilder;

use function array_values;
use function str_contains;
use function str_replace;

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

    public function toSql(PreparableSqlBuilder $builder): string
    {
        if ($this->identifiers === []) {
            return '(NULL)';
        }

        $q      = $builder->q;
        $result = '(';
        $first  = true;

        foreach ($this->identifiers as $id) {
            if (! $first) {
                $result .= ', ';
            }
            $first = false;

            $result .= str_contains($id, '.')
                ? $q . str_replace('.', $q . '.' . $q, $id) . $q
                : $q . $id . $q;
        }

        return $result . ')';
    }
}
