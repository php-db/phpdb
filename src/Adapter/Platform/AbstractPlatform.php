<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Platform;

use Override;

use function addcslashes;
use function array_map;
use function implode;
use function preg_replace;
use function str_contains;
use function str_replace;
use function trigger_error;

abstract class AbstractPlatform implements PlatformInterface
{
    /** @var string[] */
    protected $quoteIdentifier = ['"', '"'];

    /** @var string */
    protected $quoteIdentifierTo = '\'';

    /** @var bool */
    protected $quoteIdentifiers = true;

    private const SAFE_WORDS_PATTERN = '/(?<![a-zA-Z0-9_$])(?!(?:as|and|or|between)(?![a-zA-Z0-9_$]))([a-zA-Z_][a-zA-Z0-9_$]*)/i';

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function quoteIdentifierInFragment(string $identifier, array $additionalSafeWords = []): string
    {
        if (! $this->quoteIdentifiers || $identifier === '*') {
            return $identifier;
        }

        return preg_replace(self::SAFE_WORDS_PATTERN, $this->quoteIdentifier[0] . '$1' . $this->quoteIdentifier[1], $identifier);
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
}
