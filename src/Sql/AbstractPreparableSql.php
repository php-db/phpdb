<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\StatementContainerInterface;

use function array_values;
use function is_array;

abstract class AbstractPreparableSql extends AbstractSql implements PreparableSqlInterface
{
    #[Override]
    public function prepareStatement(
        AdapterInterface $adapter,
        StatementContainerInterface $statementContainer,
    ): StatementContainerInterface {
        $parameterContainer = $statementContainer->getParameterContainer();

        if (! $parameterContainer instanceof ParameterContainer) {
            $parameterContainer = new ParameterContainer();

            $statementContainer->setParameterContainer($parameterContainer);
        }

        $statementContainer->setSql(
            $this->buildSqlString($adapter->getPlatform(), $adapter->getDriver(), $parameterContainer),
        );

        return $statementContainer;
    }

    /**
     * Returns the table to render, dropping the alias from an aliased table array
     *
     * TableGateway stores an aliased table on data-changing statements, and alias syntax in those
     * statements is not portable, so only the table itself is rendered.
     *
     * @param array<string, string|TableIdentifier>|string|TableIdentifier $table
     */
    protected function unaliasTable(array|string|TableIdentifier $table): string|TableIdentifier
    {
        return is_array($table) ? array_values($table)[0] ?? '' : $table;
    }
}
