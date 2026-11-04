<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use PhpDb\Sql\Argument\Values;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;

use function array_map;
use function count;
use function implode;
use function in_array;
use function range;

/**
 * Where each argument's rendered values land in a positional placeholder sequence
 *
 * A Values argument renders one value per list item, so it occupies a run of positions rather
 * than a single one. Every other argument occupies exactly one position.
 *
 * @internal
 */
final class ArgumentPlaceholders
{
    /**
     * Whether rendering the argument binds parameters, so repeating or omitting it would misalign them
     */
    public static function bindsValues(ArgumentInterface $argument): bool
    {
        return ! in_array(
            $argument->getType(),
            [ArgumentType::Identifier, ArgumentType::Identifiers, ArgumentType::Literal],
            strict: true,
        );
    }

    /**
     * Placeholder text for every value each argument renders, keyed by argument index
     *
     * @param list<ArgumentInterface> $arguments
     * @return array<int, string>
     */
    public static function runs(array $arguments): array
    {
        $runs     = [];
        $position = 1;
        foreach ($arguments as $index => $argument) {
            $count = $argument instanceof Values ? count($argument->getValue()) : 1;

            $runs[$index] = self::run($position, $count);
            $position     += $count;
        }

        return $runs;
    }

    /**
     * Positional placeholders for $count values, starting at $position
     */
    private static function run(int $position, int $count): string
    {
        return 0 === $count
            ? 'NULL'
            : implode(', ', array_map(
                static fn(int $offset): string => "%{$offset}\$s",
                range($position, $position + $count - 1),
            ));
    }
}
