<?php

namespace PhpDb\TableGateway\Feature;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Sql;

class MasterSlaveFeature extends AbstractFeature
{
    /** @var AdapterInterface */
    protected $slaveAdapter;

    /** @var Sql */
    protected $masterSql;

    /** @var Sql */
    protected $slaveSql;

    /**
     * Constructor
     */
    public function __construct(AdapterInterface $slaveAdapter, ?Sql $slaveSql = null)
    {
        $this->slaveAdapter = $slaveAdapter;
        if ($slaveSql instanceof Sql) {
            $this->slaveSql = $slaveSql;
        }
    }

    /** @return AdapterInterface */
    public function getSlaveAdapter()
    {
        return $this->slaveAdapter;
    }

    /**
     * @return Sql
     */
    public function getSlaveSql()
    {
        return $this->slaveSql;
    }

    /**
     * after initialization, retrieve the original adapter as "master"
     */
    public function postInitialize(): void
    {
        $this->masterSql = $this->tableGateway->sql;
        if ($this->slaveSql === null) {
            $this->slaveSql = new Sql(
                $this->slaveAdapter,
                $this->tableGateway->sql->getTable()
            );
        }
    }

    /**
     * preSelect()
     * Replace adapter with slave temporarily
     */
    public function preSelect(): void
    {
        $this->tableGateway->sql = $this->slaveSql;
    }

    /**
     * postSelect()
     * Ensure to return to the master adapter
     */
    public function postSelect(): void
    {
        $this->tableGateway->sql = $this->masterSql;
    }
}
