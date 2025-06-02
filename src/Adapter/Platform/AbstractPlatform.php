<?php

namespace Laminas\Db\Adapter\Platform;

use function addcslashes;
use function array_map;
use function implode;
use function preg_split;
use function str_replace;
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
    protected $quoteIdentifierFragmentPattern = '/([^0-9,a-z,A-Z$_:])/i';

    /**
     * {@inheritDoc}
     */
    public function quoteIdentifierInFragment(string $identifier, array $safeWords = []): string
    {
        if (! $this->quoteIdentifiers) {
            return $identifier;
        }

        $safeWordsInt = ['*' => true, ' ' => true, '.' => true, 'as' => true];

        foreach ($safeWords as $sWord) {
            $safeWordsInt[strtolower($sWord)] = true;
        }

        $parts = preg_split(
            $this->quoteIdentifierFragmentPattern,
            $identifier,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );

        $identifier = '';

        foreach ($parts as $part) {
            $identifier .= isset($safeWordsInt[strtolower($part)])
                ? $part
                : $this->quoteIdentifier[0]
                . str_replace($this->quoteIdentifier[0], $this->quoteIdentifierTo, $part)
                . $this->quoteIdentifier[1];
        }

        return $identifier;
    }

    /**
     * {@inheritDoc}
     */
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
    public function quoteIdentifierChain(array|string $identifierChain): string
    {
        return '"' . implode('"."', (array) str_replace('"', '\\"', $identifierChain)) . '"';
    }

    /**
     * {@inheritDoc}
     */
    public function getQuoteIdentifierSymbol(): string
    {
        return $this->quoteIdentifier[0];
    }

    /**
     * {@inheritDoc}
     */
    public function getQuoteValueSymbol(): string
    {
        return '\'';
    }

    /**
     * {@inheritDoc}
     */
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
    public function quoteTrustedValue(int|float|string|bool $value): ?string
    {
        return '\'' . addcslashes((string) $value, "\x00\n\r\\'\"\x1a") . '\'';
    }

    /**
     * {@inheritDoc}
     */
    public function quoteValueList(array|string $valueList): string
    {
        return implode(', ', array_map([$this, 'quoteValue'], (array) $valueList));
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifierSeparator(): string
    {
        return '.';
    }
}
