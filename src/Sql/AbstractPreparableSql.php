<?php

namespace Laminas\Db\Sql;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\ParameterContainer;
use Laminas\Db\Adapter\Driver\StatementInterface;

abstract class AbstractPreparableSql extends AbstractSql implements PreparableSqlInterface
{
    /**
     * {@inheritDoc}
     *
     * @return StatementInterface
     */
    public function prepareStatement(AdapterInterface $adapter, StatementInterface $statementInterface): StatementInterface
    {
        $parameterContainer = $statementInterface->getParameterContainer();

        if (! $parameterContainer instanceof ParameterContainer) {
            $parameterContainer = new ParameterContainer();

            $statementInterface->setParameterContainer($parameterContainer);
        }

        $statementInterface->setSql(
            $this->buildSqlString($adapter->getPlatform(), $adapter->getDriver(), $parameterContainer)
        );

        return $statementInterface;
    }
}
