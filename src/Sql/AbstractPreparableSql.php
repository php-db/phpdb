<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\StatementContainerInterface;

abstract class AbstractPreparableSql extends AbstractSql implements PreparableSqlInterface
{
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
