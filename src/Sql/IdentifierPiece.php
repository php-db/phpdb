<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Adapter\Platform\PlatformInterface;

use function preg_match;
use function strtolower;

/**
 * What a piece of an identifier string is, read between spaces, qualifier dots and `*`
 *
 * @internal
 */
enum IdentifierPiece
{
    /** A qualifier dot */
    case Dot;

    /** A `*` directly after a qualifier dot */
    case QualifiedStar;

    /** A `*` operator or an `AS` keyword, which fragment quoting leaves bare */
    case Keyword;

    /** A name fragment quoting renders as one identifier */
    case Name;

    /** A name fragment quoting would split into several quoted pieces */
    case Whole;

    /** An operator or expression syntax, which no identifier quoting renders as written */
    case Unquotable;

    /** A piece with no letter, digit or underscore, such as `-` or `=`, is an operator */
    private const string OPERATOR = '/^[^\p{L}\p{N}_$]+$/u';

    private const string EXPRESSION_CHARACTERS = '/[()=<>+\/%|!~^&;]/';

    public static function of(string $piece, ?self $previous, bool $spaced, PlatformInterface $platform): self
    {
        if ('.' === $piece) {
            return self::Dot;
        }

        if ('*' === $piece) {
            return self::Dot === $previous ? self::QualifiedStar : self::Keyword;
        }

        $isName = $platform->quoteIdentifierInFragment($piece) === $platform->quoteIdentifier($piece);

        return match (true) {
            $spaced && 'as' === strtolower($piece) => self::Keyword,
            1 === preg_match(self::OPERATOR, $piece)
                || (! $isName && 1 === preg_match(self::EXPRESSION_CHARACTERS, $piece))
                => self::Unquotable,
            $isName => self::Name,
            default => self::Whole,
        };
    }
}
