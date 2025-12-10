<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\PreparableSqlInterface;

use function array_map;
use function array_values;
use function implode;
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

    public function getSpecification(): string
    {
        if ($this->identifiers === []) {
            return '(NULL)';
        }

        $marked = array_map(
            static fn(string $id): string => PreparableSqlInterface::P_LQUOTE
                . str_replace(
                    '.',
                    PreparableSqlInterface::P_RQUOTE . '.' . PreparableSqlInterface::P_LQUOTE,
                    $id
                )
                . PreparableSqlInterface::P_RQUOTE,
            $this->identifiers
        );

        return '(' . implode(', ', $marked) . ')';
    }
}
