<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\Predicate\Exception\InvalidArgumentException;

use function array_count_values;
use function count;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function str_contains;
use function str_replace;

/**
 * Aligns a user-supplied predicate specification with the values its arguments render
 *
 * A custom specification refers to each argument with a single placeholder, plain (%s) or
 * positional (%2$s). Each placeholder is replaced by the positional placeholders for every value
 * its argument renders, which ArgumentPlaceholders maps.
 *
 * Only the string conversion is supported. Width, precision, sign, padding and alignment
 * modifiers are accepted but ignored, because the value they would format is a placeholder name
 * rather than a value. A literal percent must be written as %%, which is left in place for the
 * caller to unescape exactly once.
 *
 * @internal
 */
final class CustomSpecification
{
    /**
     * A placeholder in plain (%s) or positional (%2$s) form, with any sprintf modifiers
     *
     * Escaped percents are skipped, so they never match and are therefore never rewritten.
     */
    private const string PLACEHOLDER = '/%%(*SKIP)(*FAIL)|%(?:(\d+)\$)?[-+ 0\']*\d*(?:\.\d+)?([a-zA-Z])/';

    /**
     * A positional placeholder, and any placeholder that is not positional
     *
     * Escaped percents are skipped, so they never count as either style.
     */
    private const string POSITIONAL_PLACEHOLDER = '/%%(*SKIP)(*FAIL)|%\d+\$/';
    private const string PLAIN_PLACEHOLDER      = '/%%(*SKIP)(*FAIL)|%(?!\d+\$)/';

    /**
     * @param list<ArgumentInterface> $arguments
     * @throws InvalidArgumentException When a placeholder names a missing argument, an argument
     *     that binds values is not referenced exactly once, a conversion other than the string
     *     conversion is used, plain and positional placeholders are mixed, or a percent sign is
     *     not escaped.
     */
    public static function expand(string $specification, array $arguments): string
    {
        self::assertPlaceholdersAreWellFormed($specification);

        $runs       = ArgumentPlaceholders::runs($arguments);
        $referenced = [];
        $sequential = 0;

        $expanded = preg_replace_callback(
            self::PLACEHOLDER,
            static function (array $match) use ($specification, $runs, &$referenced, &$sequential): string {
                [$placeholders, $index] = self::expandPlaceholder(
                    match: $match,
                    specification: $specification,
                    runs: $runs,
                    sequential: $sequential,
                );

                $referenced[] = $index;

                return $placeholders;
            },
            $specification,
        );

        self::assertBoundArgumentsReferencedOnce($specification, $arguments, $referenced);

        return $expanded ?? '';
    }

    /**
     * Reject a specification that does not reference every bound argument exactly once
     *
     * @param list<ArgumentInterface> $arguments
     * @param list<int> $referenced
     */
    private static function assertBoundArgumentsReferencedOnce(
        string $specification,
        array $arguments,
        array $referenced,
    ): void {
        $references = array_count_values($referenced);
        foreach ($arguments as $index => $argument) {
            $count = $references[$index] ?? 0;
            if (1 !== $count && ArgumentPlaceholders::bindsValues($argument)) {
                throw InvalidArgumentException::forSpecificationArgumentNotReferencedOnce(
                    $specification,
                    $index + 1,
                    $count,
                );
            }
        }
    }

    /**
     * Reject a specification whose placeholders and percent signs cannot be expanded
     */
    private static function assertPlaceholdersAreWellFormed(string $specification): void
    {
        $literals = preg_replace(
            pattern: self::PLACEHOLDER,
            replacement: '',
            subject: $specification,
        );

        if (str_contains(str_replace(
            search: '%%',
            replace: '',
            subject: (string) $literals,
        ), '%')) {
            throw InvalidArgumentException::forSpecificationUnsupportedConversion($specification, '%');
        }

        $positional = preg_match(self::POSITIONAL_PLACEHOLDER, $specification);
        $plain      = preg_match(self::PLAIN_PLACEHOLDER, $specification);

        if (1 === $positional && 1 === $plain) {
            throw InvalidArgumentException::forSpecificationMixedPlaceholderStyles($specification);
        }
    }

    /**
     * Placeholders for one match, and the index of the argument it references
     *
     * @param array<int|string, string> $match
     * @param array<int, string> $runs
     * @return array{string, int}
     */
    private static function expandPlaceholder(
        array $match,
        string $specification,
        array $runs,
        int &$sequential,
    ): array {
        if ('s' !== ($match[2] ?? '')) {
            throw InvalidArgumentException::forSpecificationUnsupportedConversion(
                $specification,
                $match[0] ?? '',
            );
        }

        $positional = $match[1] ?? '';
        $index      = '' === $positional ? $sequential++ : (int) $positional - 1;

        return [
            $runs[$index] ?? throw InvalidArgumentException::forSpecificationArgumentOutOfRange(
                $specification,
                $index + 1,
                count($runs),
            ),
            $index,
        ];
    }
}
