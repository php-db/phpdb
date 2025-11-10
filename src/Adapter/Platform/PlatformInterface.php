<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Platform;

use PhpDb\Sql\Platform\PlatformDecoratorInterface;

interface PlatformInterface
{
    /**
     * Get name
     */
    public function getName(): string;

    /**
     * Get Sql platform decorator
     *
     * Returns a PhpDb\Sql\Platform\* instance
     * PhpDb\Sql\Platform\AbstractPlatform implements this interface
     */
    public function getSqlPlatformDecorator(): PlatformDecoratorInterface;

    /**
     * Get quote identifier symbol
     */
    public function getQuoteIdentifierSymbol(): string;

    /**
     * Quote identifier
     */
    public function quoteIdentifier(string $identifier): string;

    /**
     * Quote identifier chain
     *
     * @param string|string[] $identifierChain
     */
    public function quoteIdentifierChain(array|string $identifierChain): string;

    /**
     * Get quote value symbol
     */
    public function getQuoteValueSymbol(): string;

    /**
     * Quote value
     *
     * Will throw a notice when used in a workflow that can be considered "unsafe"
     */
    public function quoteValue(string $value): string;

    /**
     * Quote Trusted Value
     *
     * The ability to quote values without notices
     */
    public function quoteTrustedValue(int|float|string|bool $value): ?string;

    /**
     * Quote value list
     *
     * @param string|string[] $valueList
     */
    public function quoteValueList(array|string $valueList): string;

    /**
     * Get identifier separator
     */
    public function getIdentifierSeparator(): string;

    /**
     * Quote identifier in fragment
     */
    public function quoteIdentifierInFragment(string $identifier, array $additionalSafeWords = []): string;
}
