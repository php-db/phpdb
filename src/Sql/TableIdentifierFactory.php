<?php

declare(strict_types=1);

namespace PhpDb\Sql;

/**
 * Callable factory producing {@see TableIdentifier} instances with a
 * preconfigured table prefix and separator.
 *
 * Register via {@see \PhpDb\ConfigProvider} and configure the prefix once
 * (e.g. in application config) to have every identifier created through the
 * factory share the same prefix — convenient for creating backup_* tables
 * during a migration.
 */
final readonly class TableIdentifierFactory
{
    /**
     * @param null|string $separator Null falls back to {@see TableIdentifier::SEPARATOR}.
     * @throws Exception\InvalidArgumentException If $prefix or $separator is an empty string.
     */
    public function __construct(
        private ?string $prefix = null,
        private ?string $separator = TableIdentifier::SEPARATOR,
    ) {
        if ('' === $prefix) {
            throw Exception\InvalidArgumentException::forEmptyPrefix();
        }

        if ('' === $separator) {
            throw Exception\InvalidArgumentException::forEmptySeparator();
        }
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function getSeparator(): string
    {
        return $this->separator ?? TableIdentifier::SEPARATOR;
    }

    /**
     * Creates a TableIdentifier carrying the configured prefix and separator.
     *
     * A prefix or separator passed at call time takes precedence over the
     * configured one.
     *
     * @throws Exception\InvalidArgumentException If $prefix or $separator is an empty string.
     */
    public function __invoke(
        string $table,
        ?string $schema = null,
        ?string $prefix = null,
        ?string $separator = null,
    ): TableIdentifier {
        return new TableIdentifier($table, $schema, $prefix ?? $this->prefix, $separator ?? $this->getSeparator());
    }
}
