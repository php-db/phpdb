<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Platform;

use Laminas\Db\Sql\Platform\PlatformDecoratorInterface;

interface PlatformInterface
{
    /**
     * Get name
     */
    public function getName(): string;

    /**
     * todo: sat-migration document
     * Get Sql platform decorator
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
     */
    public function quoteIdentifierChain(string|array $identifierChain): string;

    /**
     * Get quote value symbol
     *
     * @return string
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
     *
     * @param scalar $value
     */
    public function quoteTrustedValue($value): string;

    /**
     * Quote value list
     */
    public function quoteValueList(string|array $valueList): string;

    /**
     * Get identifier separator
     */
    public function getIdentifierSeparator(): string;

    /**
     * Quote identifier in fragment
     */
    public function quoteIdentifierInFragment(string $identifier, array $additionalSafeWords = []): string;
}
