<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Adapter\Platform\PlatformInterface;

use function preg_split;

use const PREG_SPLIT_DELIM_CAPTURE;
use const PREG_SPLIT_NO_EMPTY;

/**
 * How an identifier string must be quoted to render as written
 *
 * The string is read in pieces between spaces, qualifier dots and `*`, each classified by IdentifierPiece.
 *
 * @internal
 */
enum IdentifierShape
{
    /** Fragment quoting renders it as written */
    case Fragment;

    /** It holds a name that fragment quoting would split, so each qualifier part is quoted whole */
    case Whole;

    /** It holds expression syntax that no identifier quoting renders as written */
    case Unquotable;

    /** It is empty, or a qualifier dot leaves an empty part */
    case EmptyPart;

    /**
     * The pattern must keep exactly one capturing group, so spaces, dots and `*` come back as pieces
     * of their own
     */
    private const string PIECES = '/([ .*])/';

    public static function of(string $name, PlatformInterface $platform): self
    {
        $whole    = false;
        $keyword  = false;
        $previous = null;
        $spaced   = false;

        /** @var list<string> $pieces */
        $pieces = preg_split(self::PIECES, $name, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        foreach ($pieces as $piece) {
            if (' ' === $piece) {
                $spaced = true;
                continue;
            }

            $kind = IdentifierPiece::of($piece, $previous, $spaced, $platform);
            if (IdentifierPiece::Unquotable === $kind) {
                return self::Unquotable;
            }

            if (IdentifierPiece::Dot === $kind && (null === $previous || IdentifierPiece::Dot === $previous)) {
                return self::EmptyPart;
            }

            $whole =
                $whole
                || IdentifierPiece::Whole === $kind
                || (IdentifierPiece::Name === $kind && IdentifierPiece::Name === $previous && $spaced);
            $keyword  = $keyword || IdentifierPiece::Keyword === $kind;
            $previous = $kind;
            $spaced   = false;
        }

        return match (true) {
            null === $previous, IdentifierPiece::Dot === $previous => self::EmptyPart,
            $whole  => $keyword ? self::Unquotable : self::Whole,
            default => self::Fragment,
        };
    }
}
