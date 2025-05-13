<?php

namespace Laminas\Db\Sql;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\ParameterContainer;
use Laminas\Db\Adapter\StatementContainerInterface;

abstract class AbstractPreparableSql extends AbstractSql implements PreparableSqlInterface
{
    /**
     * {@inheritDoc}
     *
     * @return StatementContainerInterface
     */
    public function prepareStatement(AdapterInterface $adapter, StatementContainerInterface $statementContainer)
    {
        // todo: parameterContainer is not instanceof ParameterContainer when
        // Mysqli is used?
        $parameterContainer = $statementContainer->getParameterContainer();

        if (! $parameterContainer instanceof ParameterContainer) {
            $parameterContainer = new ParameterContainer();
            // todo: setting empty parameter container with mapped parameters and mysqli adapter
            $statementContainer->setParameterContainer($parameterContainer);
        }

        $statementContainer->setSql(
            $this->buildSqlString($adapter->getPlatform(), $adapter->getDriver(), $parameterContainer)
        );

        return $statementContainer;
    }
}
