<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\Sql92;
use PhpDb\Adapter\StatementContainerInterface;

abstract class AbstractPreparableSql extends AbstractSql implements PreparableSqlInterface
{
    /**
     * String representation for debugging and test output.
     */
    public function __toString(): string
    {
        return $this->getSqlString(new Sql92());
    }

    /**
     * @deprecated Use prepareSqlString() with a PreparableSqlBuilder instead.
     */
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

    /** @inheritDoc */
    #[Override]
    public function prepareSqlString(PreparableSqlBuilder $builder): string
    {
        return $this->buildSqlString(
            $builder->getPlatform(),
            $builder->getDriver(),
            $builder->getParameterContainer()
        );
    }
}
