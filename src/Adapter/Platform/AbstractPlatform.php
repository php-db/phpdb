<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Platform;

use Override;
use PDO;
use PhpDb\Adapter\Driver;
use PhpDb\Adapter\Exception;

use function addcslashes;
use function array_map;
use function implode;
use function preg_split;
use function str_replace;
use function strtolower;

use const PREG_SPLIT_DELIM_CAPTURE;
use const PREG_SPLIT_NO_EMPTY;

/**
 * @api
 *
 * @property Driver\DriverInterface|Driver\PdoDriverInterface|PDO $driver
 */
abstract class AbstractPlatform implements PlatformInterface
{
    /** @var array{string, string} The opening and closing identifier quotes */
    protected array $quoteIdentifier = ['"', '"'];

    protected string $quoteIdentifierTo = '\'';

    protected bool $quoteIdentifiers = true;

    protected string $quoteIdentifierFragmentPattern = '/([^0-9,a-zA-Z$_:])/i';

    /** @var array<string, true> */
    private const array SAFE_WORDS = ['*' => true, ' ' => true, '.' => true, 'as' => true];

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getIdentifierSeparator(): string
    {
        return '.';
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
    public function quoteIdentifier(string $identifier): string
    {
        if (! $this->quoteIdentifiers) {
            return $identifier;
        }

        return (
            $this->quoteIdentifier[0]
                . str_replace($this->quoteIdentifier[0], $this->quoteIdentifierTo, $identifier)
                . $this->quoteIdentifier[1]
        );
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function quoteIdentifierChain(array|string $identifierChain): string
    {
        return '"' . implode('"."', (array) str_replace('"', replace: '\\"', subject: $identifierChain)) . '"';
    }

    /**
     * {@inheritDoc}
     *
     * @param array<array-key, string> $additionalSafeWords
     */
    #[Override]
    public function quoteIdentifierInFragment(string $identifier, array $additionalSafeWords = []): string
    {
        if (! $this->quoteIdentifiers) {
            return $identifier;
        }

        $safeWords = self::SAFE_WORDS;
        foreach ($additionalSafeWords as $sWord) {
            $safeWords[strtolower($sWord)] = true;
        }

        $parts = preg_split(
            $this->quoteIdentifierFragmentPattern,
            $identifier,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY,
        );

        [$quoteStart, $quoteEnd] = $this->quoteIdentifier;
        $quoteTo = $this->quoteIdentifierTo;
        $result  = '';

        foreach (false === $parts ? [] : $parts as $part) {
            $result .= $safeWords[strtolower($part)] ?? false
                ? $part
                : $quoteStart . str_replace($quoteStart, $quoteTo, $part) . $quoteEnd;
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function quoteTrustedValue(int|float|string|bool $value): ?string
    {
        return '\'' . addcslashes((string) $value, characters: "\x00\n\r\\'\"\x1a") . '\'';
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException When no driver is available to quote safely.
     */
    #[Override]
    public function quoteValue(string $value): string
    {
        if (null === ($this->driver ?? null)) {
            throw Exception\RuntimeException::forVulnerablePlatformQuote(static::class, __METHOD__);
        }
        return '\'' . addcslashes($value, characters: "\x00\n\r\\'\"\x1a") . '\'';
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function quoteValueList(array|string $valueList): string
    {
        return implode(', ', array_map([$this, 'quoteValue'], (array) $valueList));
    }
}
