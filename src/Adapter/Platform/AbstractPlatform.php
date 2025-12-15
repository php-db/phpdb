<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Platform;

use Override;

use function addcslashes;
use function array_map;
use function implode;
use function is_bool;
use function is_float;
use function is_int;
use function preg_replace;
use function str_contains;
use function str_replace;
use function strtr;
use function trigger_error;

abstract class AbstractPlatform implements PlatformInterface
{
    /** @var string[] */
    protected $quoteIdentifier = ['"', '"'];

    public string $q = '"';

    /** @var string */
    protected $quoteIdentifierTo = '\'';

    /** @var bool */
    protected $quoteIdentifiers = true;

    /** @var string */
    protected $quoteIdentifierFragmentPattern = '/([^0-9,a-zA-Z$_:])/i';

    private const KEYWORDS_PATTERN = 'AND|OR|ON|IS|NOT|NULL|TRUE|FALSE|IN|LIKE|BETWEEN|AS';

    /** @var array{0: string, 1: string}|null Cached regex patterns for quoteIdentifierInFragment */
    private ?array $identifierPatterns = null;

    /** @var array{0: string, 1: string}|null Cached replacements for quoteIdentifierInFragment */
    private ?array $identifierReplacements = null;

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function quoteIdentifierInFragment(string $identifier, array $additionalSafeWords = []): string
    {
        if (! $this->quoteIdentifiers) {
            return $identifier;
        }

        // Lazy-build and cache patterns/replacements on first use
        if ($this->identifierPatterns === null) {
            $q  = $this->quoteIdentifier[0];
            $qe = preg_quote($q, '/');

            $this->identifierPatterns = [
                '/([A-Za-z_]\w*)\.([A-Za-z_]\w*)/S',
                '/(?<!' . $qe . ')\b(?!(?:' . self::KEYWORDS_PATTERN . ')\b)([A-Za-z_]\w*+)\b(?!' . $qe . '|\s*\()/iS',
            ];
            $this->identifierReplacements = [
                $q . '$1' . $q . '.' . $q . '$2' . $q,
                $q . '$1' . $q,
            ];
        }

        return preg_replace($this->identifierPatterns, $this->identifierReplacements, $identifier);
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
            $sql         = preg_replace('/\{\?\}/', $quotedValue, $sql, 1);
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
