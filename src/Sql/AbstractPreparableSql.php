<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\StatementContainerInterface;

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
        return self::P_LQUOTE
            . str_replace('.', self::P_RQUOTE . '.' . self::P_LQUOTE, $identifier)
            . self::P_RQUOTE;
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
