<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use PhpDb\Sql\Argument\Values;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Predicate\Exception\InvalidArgumentException;

use function array_count_values;
use function array_map;
use function count;
use function implode;
use function in_array;
use function preg_split;
use function range;
use function substr;

use const PREG_SPLIT_DELIM_CAPTURE;

/**
 * Aligns a user-supplied predicate specification with the values its arguments render
 *
 * A custom specification refers to each argument with a single placeholder, plain (%s) or
 * positional (%2$s), but a Values argument renders one value per list item. Each placeholder
 * is replaced by positional placeholders for every value its argument renders, so %2$s becomes
 * %2$s, %3$s for a two-value list and NULL for an empty one.
 *
 * @internal
 */
final class CustomSpecification
{
    /**
     * @param list<ArgumentInterface> $arguments
     * @throws InvalidArgumentException When a placeholder names a missing argument, or an
     *     argument that binds values is not referenced exactly once.
     */
    public static function expand(string $specification, array $arguments): string
    {
        $offsets    = self::offsets($arguments);
        $referenced = [];
        $sequential = 0;
        $expanded   = '';

        /** @var list<string> $parts */
        $parts = preg_split('/%%(*SKIP)(*FAIL)|(%(?:\d+\$)?s)/', $specification, flags: PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $part => $text) {
            if (0 === ($part % 2)) {
                $expanded .= $text;
                continue;
            }

            $index    = '%s' === $text ? $sequential++ : (int) substr($text, offset: 1, length: -2) - 1;
            $argument = $arguments[$index] ?? throw InvalidArgumentException::forSpecificationArgumentOutOfRange(
                    $specification,
                    $index + 1,
                    count($arguments),
                );

            $referenced[] = $index;
            $expanded     .= self::placeholders($argument, $offsets[$index] ?? 1);
        }

        $references = array_count_values($referenced);
        foreach ($arguments as $index => $argument) {
            $count = $references[$index] ?? 0;
            if (1 !== $count && self::bindsValues($argument)) {
                throw InvalidArgumentException::forSpecificationArgumentNotReferencedOnce(
                    $specification,
                    $index + 1,
                    $count,
                );
            }
        }

        return $expanded;
    }

    /**
     * Whether rendering the argument binds parameters, so repeating or omitting it would misalign them
     */
    private static function bindsValues(ArgumentInterface $argument): bool
    {
        return ! in_array(
            $argument->getType(),
            [ArgumentType::Identifier, ArgumentType::Identifiers, ArgumentType::Literal],
            strict: true,
        );
    }

    /**
     * Position of each argument's first rendered value
     *
     * @param list<ArgumentInterface> $arguments
     * @return array<int, int>
     */
    private static function offsets(array $arguments): array
    {
        $offsets  = [];
        $position = 1;
        foreach ($arguments as $index => $argument) {
            $offsets[$index] = $position;
            $position        += $argument instanceof Values ? count($argument->getValue()) : 1;
        }

        return $offsets;
    }

    /**
     * Positional placeholders for every value an argument renders, starting at $offset
     */
    private static function placeholders(ArgumentInterface $argument, int $offset): string
    {
        $count = $argument instanceof Values ? count($argument->getValue()) : 1;

        return 0 === $count
            ? 'NULL'
            : implode(', ', array_map(
                static fn(int $position): string => "%{$position}\$s",
                range($offset, $offset + $count - 1),
            ));
    }
}
