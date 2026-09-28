<?php

declare(strict_types=1);

namespace PhpDb\TableGateway\Feature;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Sql;
use PhpDb\TableGateway\Exception;

final class MasterSlaveFeature extends AbstractFeature
{
    protected AdapterInterface $slaveAdapter;

    protected ?Sql $masterSql = null;

    protected ?Sql $slaveSql = null;

    public function __construct(AdapterInterface $slaveAdapter, ?Sql $slaveSql = null)
    {
        $this->slaveAdapter = $slaveAdapter;
        if ($slaveSql instanceof Sql) {
            $this->slaveSql = $slaveSql;
        }
    }

    public function getSlaveAdapter(): AdapterInterface
    {
        return $this->slaveAdapter;
    }

    public function getSlaveSql(): ?Sql
    {
        return $this->slaveSql;
    }

    /**
     * after initialization, retrieve the original adapter as "master"
     *
     * @throws Exception\RuntimeException
     */
    public function postInitialize(): void
    {
        $masterSql = $this->tableGateway->sql;
        if (! $masterSql instanceof Sql) {
            throw new Exception\RuntimeException(
                'The table gateway must be initialized with a Sql instance before this feature is applied.',
            );
        }

        $this->masterSql = $masterSql;
        if (null === $this->slaveSql) {
            $this->slaveSql = new Sql(
                $this->slaveAdapter,
                $masterSql->getTable(),
            );
        }
    }

    /**
     * postSelect()
     * Ensure to return to the master adapter
     *
     * @throws Exception\RuntimeException
     */
    public function postSelect(): void
    {
        if (! $this->masterSql instanceof Sql) {
            throw new Exception\RuntimeException(
                'The master Sql instance is not available; postInitialize() has not been run.',
            );
        }

        $this->tableGateway->sql = $this->masterSql;
    }

    /**
     * preSelect()
     * Replace adapter with slave temporarily
     */
    public function preSelect(): void
    {
        $this->tableGateway->sql = $this->slaveSql;
    }
}
