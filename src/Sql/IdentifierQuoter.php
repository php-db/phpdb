<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Adapter\Platform\PlatformInterface;

use function array_map;
use function count;
use function explode;
use function implode;
use function preg_match;
use function preg_split;
use function trim;

/**
 * Quotes identifier strings, keeping fragment quoting wherever it renders them as written
 *
 * Fragment quoting quotes every word the platform splits out, which renders qualified names (`t.col`)
 * and simple expressions (`price*qty`) correctly but breaks a name holding any other character
 * (`x-y`, `my col`) into adjacent quoted pieces. Such a name is quoted whole instead, split only on
 * the qualifier dot. A string holding other expression syntax, such as an operator or a function call,
 * or mixing a whole-quoted name with `*` or `AS`, has no correct quoting, so it is rejected; pass it as
 * an Expression instead.
 *
 * @internal
 */
final class IdentifierQuoter
{
    /**
     * Word characters and qualifier dots, optionally ending in `.*`, which fragment quoting renders as
     * written on every platform; a part that is the `AS` keyword is left to the full check
     */
    private const string PLAIN_NAME = '/^(?!as\b)\w+(?:\.(?!as\b)\w+)*(?:\.\*)?\z/i';

    private const string ALIAS_KEYWORD = '/\s+as\s+/i';

    /**
     * @throws Exception\InvalidArgumentException When the name has an empty part, or holds expression
     *     syntax that no identifier quoting renders as written.
     */
    public static function quote(string $name, PlatformInterface $platform): string
    {
        if (1 === preg_match(self::PLAIN_NAME, $name)) {
            return $platform->quoteIdentifierInFragment($name);
        }

        return match (IdentifierShape::of($name, $platform)) {
            IdentifierShape::Fragment   => $platform->quoteIdentifierInFragment($name),
            IdentifierShape::Whole      => self::quoteChain($name, $platform),
            IdentifierShape::Unquotable => throw Exception\InvalidArgumentException::forUnquotableIdentifier($name),
            IdentifierShape::EmptyPart  => throw Exception\InvalidArgumentException::forEmptyIdentifierPart($name),
        };
    }

    /**
     * Quote a column, quoting each side of an inline `AS` alias separately where the whole cannot be
     *
     * @throws Exception\InvalidArgumentException
     */
    public static function quoteColumn(string $column, PlatformInterface $platform): string
    {
        if (
            1 !== preg_match(self::PLAIN_NAME, $column)
            && IdentifierShape::Unquotable === IdentifierShape::of($column, $platform)
        ) {
            /** @var list<string> $parts */
            $parts = preg_split(self::ALIAS_KEYWORD, $column);
            if (2 === count($parts)) {
                /** @var array{string, string} $parts */
                [$name, $alias] = $parts;

                return self::quote($name, $platform) . ' AS ' . self::quote($alias, $platform);
            }
        }

        return self::quote($column, $platform);
    }

    /**
     * Quote each qualifier part whole, leaving a `*` part bare
     */
    private static function quoteChain(string $name, PlatformInterface $platform): string
    {
        return implode(
            $platform->getIdentifierSeparator(),
            array_map(
                static fn(string $part): string => '*' === $part ? $part : $platform->quoteIdentifier($part),
                array_map(trim(...), explode('.', $name)),
            ),
        );
    }
}
