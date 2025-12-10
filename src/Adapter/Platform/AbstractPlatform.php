<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Platform;

use Override;

use function addcslashes;
use function array_map;
use function count;
use function explode;
use function implode;
use function preg_split;
use function str_contains;
use function str_replace;
use function strpbrk;
use function strtolower;
use function trigger_error;

use const PREG_SPLIT_DELIM_CAPTURE;
use const PREG_SPLIT_NO_EMPTY;

abstract class AbstractPlatform implements PlatformInterface
{
    /** @var string[] */
    protected $quoteIdentifier = ['"', '"'];

    /** @var string */
    protected $quoteIdentifierTo = '\'';

    /** @var bool */
    protected $quoteIdentifiers = true;

    /** @var string */
    protected $quoteIdentifierFragmentPattern = '/([^0-9,a-zA-Z$_:])/i';

    /** @var array<string, true> */
    private const SAFE_WORDS = ['*' => true, ' ' => true, '.' => true, 'as' => true];

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function quoteIdentifierInFragment(string $identifier, array $additionalSafeWords = []): string
    {
        if (! $this->quoteIdentifiers) {
            return $identifier;
        }

        if ($additionalSafeWords === []) {
            if (strpbrk($identifier, ' =(') === false) {
                $quoteStart = $this->quoteIdentifier[0];
                $quoteEnd   = $this->quoteIdentifier[1];
                $quoteTo    = $this->quoteIdentifierTo;

                if (! str_contains($identifier, '.')) {
                    // Handle standalone * (safe word)
                    if ($identifier === '*') {
                        return '*';
                    }
                    return $quoteStart . str_replace($quoteStart, $quoteTo, $identifier) . $quoteEnd;
                }

                $parts = explode('.', $identifier);
                if (count($parts) === 2) {
                    // Handle table.* pattern - * is a safe word
                    if ($parts[1] === '*') {
                        return $quoteStart . str_replace($quoteStart, $quoteTo, $parts[0]) . $quoteEnd . '.*';
                    }
                    return $quoteStart . str_replace($quoteStart, $quoteTo, $parts[0]) . $quoteEnd
                        . '.'
                        . $quoteStart . str_replace($quoteStart, $quoteTo, $parts[1]) . $quoteEnd;
                }
            }
        }

        $safeWords = self::SAFE_WORDS;
        foreach ($additionalSafeWords as $sWord) {
            $safeWords[strtolower($sWord)] = true;
        }

        $parts = preg_split(
            $this->quoteIdentifierFragmentPattern,
            $identifier,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );

        $quoteStart = $this->quoteIdentifier[0];
        $quoteEnd   = $this->quoteIdentifier[1];
        $quoteTo    = $this->quoteIdentifierTo;
        $result     = '';

        foreach ($parts as $part) {
            $lowerPart = strtolower($part);
            if (isset($safeWords[$lowerPart])) {
                $result .= $part;
            } else {
                $result .= $quoteStart . str_replace($quoteStart, $quoteTo, $part) . $quoteEnd;
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function quoteIdentifier(string $identifier): string
    {
        if (! $this->quoteIdentifiers) {
            return $identifier;
        }

        return $this->quoteIdentifier[0]
            . str_replace($this->quoteIdentifier[0], $this->quoteIdentifierTo, $identifier)
            . $this->quoteIdentifier[1];
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function quoteIdentifierChain(array|string $identifierChain): string
    {
        return '"' . implode('"."', (array) str_replace('"', '\\"', $identifierChain)) . '"';
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getQuoteIdentifierSymbol(): string
    {
        return $this->quoteIdentifier[0];
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getQuoteValueSymbol(): string
    {
        return '\'';
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function quoteValue(string $value): string
    {
        trigger_error(
            'Attempting to quote a value in ' . static::class
            . ' without extension/driver support can introduce security vulnerabilities in a production environment'
        );
        return '\'' . addcslashes($value, "\x00\n\r\\'\"\x1a") . '\'';
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function quoteTrustedValue(int|float|string|bool $value): ?string
    {
        return '\'' . addcslashes((string) $value, "\x00\n\r\\'\"\x1a") . '\'';
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function quoteValueList(array|string $valueList): string
    {
        return implode(', ', array_map([$this, 'quoteValue'], (array) $valueList));
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getIdentifierSeparator(): string
    {
        return '.';
    }

    /**
     * Assemble SQL parts by replacing identifier markers with actual quote
     * characters and value placeholders with quoted values.
     *
     * This enables deferred quoting for better performance - identifiers are
     * wrapped with markers during build phase, then all replaced in a single pass.
     *
     * @param string $sql    SQL with {"identifier"} markers and {?} value placeholders
     * @param array  $values Values to substitute for {?} placeholders (in order)
     * @return string Fully quoted SQL string
     */
    public function assembleSqlParts(string $sql, array $values): string
    {
        // Replace identifier markers with actual quote characters (single pass)
        $sql = strtr($sql, [
            '{"' => $this->quoteIdentifier[0],
            '"}' => $this->quoteIdentifier[1],
        ]);

        // Replace value placeholders sequentially
        foreach ($values as $value) {
            $quotedValue = $this->quoteValueForAssembly($value);
            $sql = preg_replace('/\{\?\}/', $quotedValue, $sql, 1);
        }

        return $sql;
    }

    /**
     * Quote a value for assembly. Handles null, bool, numeric and string types.
     */
    protected function quoteValueForAssembly(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $this->quoteTrustedValue($value);
    }
}
