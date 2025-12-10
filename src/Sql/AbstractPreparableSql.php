<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\StatementContainerInterface;

use function str_contains;
use function str_replace;

abstract class AbstractPreparableSql extends AbstractSql implements PreparableSqlInterface
{
    /**
     * Wrap an identifier with quote markers for deferred quoting.
     * Handles qualified identifiers (table.column) by wrapping each part.
     *
     * Examples:
     *   markIdentifier('column')       -> '{"column"}'
     *   markIdentifier('table.column') -> '{"table"}.{"column"}'
     */
    protected static function markIdentifier(string $identifier): string
    {
        // Fast path for simple identifiers without dots
        if (! str_contains($identifier, '.')) {
            return self::P_LQUOTE . $identifier . self::P_RQUOTE;
        }

        return self::P_LQUOTE
            . str_replace('.', self::P_RQUOTE . '.' . self::P_LQUOTE, $identifier)
            . self::P_RQUOTE;
    }

    /**
     * Mark a qualified identifier when table and column are already separate.
     * This is faster than markIdentifier() for qualified names since it avoids str_replace.
     *
     * Example:
     *   markQualifiedIdentifier('users', 'id') -> '{"users"}.{"id"}'
     */
    protected static function markQualifiedIdentifier(string $table, string $column): string
    {
        return self::P_LQUOTE . $table . self::P_RQUOTE
            . '.' . self::P_LQUOTE . $column . self::P_RQUOTE;
    }

    /**
     * Mark a simple identifier without dot checking.
     * Use when you know the identifier doesn't contain dots.
     *
     * Example:
     *   markSimpleIdentifier('id') -> '{"id"}'
     */
    protected static function markSimpleIdentifier(string $identifier): string
    {
        return self::P_LQUOTE . $identifier . self::P_RQUOTE;
    }

    #[Override]
    public function prepareStatement(
        AdapterInterface $adapter,
        StatementContainerInterface $statementContainer
    ): StatementContainerInterface {
        $parameterContainer = $statementContainer->getParameterContainer();

        if (! $parameterContainer instanceof ParameterContainer) {
            $parameterContainer = new ParameterContainer();

            $statementContainer->setParameterContainer($parameterContainer);
        }

        $statementContainer->setSql(
            $this->buildSqlString($adapter->getPlatform(), $adapter->getDriver(), $parameterContainer)
        );

        return $statementContainer;
    }
}
