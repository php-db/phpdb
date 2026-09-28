<?php

declare(strict_types=1);

namespace PhpDb\TableGateway\Feature;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Sql;
use PhpDb\TableGateway\Exception;

class PrimaryReplicaFeature extends AbstractFeature
{
    protected AdapterInterface $replicaAdapter;

    protected ?Sql $primarySql = null;

    protected ?Sql $replicaSql = null;

    public function __construct(AdapterInterface $replicaAdapter, ?Sql $replicaSql = null)
    {
        $this->replicaAdapter = $replicaAdapter;
        if ($replicaSql instanceof Sql) {
            $this->replicaSql = $replicaSql;
        }
    }

    public function getReplicaAdapter(): AdapterInterface
    {
        return $this->replicaAdapter;
    }

    public function getReplicaSql(): ?Sql
    {
        return $this->replicaSql;
    }

    /**
     * after initialization, retrieve the original adapter as "primary"
     *
     * @throws Exception\RuntimeException
     */
    public function postInitialize(): void
    {
        $primarySql = $this->tableGateway->sql;
        if (! $primarySql instanceof Sql) {
            throw new Exception\RuntimeException(
                'The table gateway must be initialized with a Sql instance before this feature is applied.',
            );
        }

        $this->primarySql = $primarySql;
        if (null === $this->replicaSql) {
            $this->replicaSql = new Sql(
                $this->replicaAdapter,
                $primarySql->getTable(),
            );
        }
    }

    /**
     * postSelect()
     * Ensure to return to the primary adapter
     *
     * @throws Exception\RuntimeException
     */
    public function postSelect(): void
    {
        if (! $this->primarySql instanceof Sql) {
            throw new Exception\RuntimeException(
                'The primary Sql instance is not available; postInitialize() has not been run.',
            );
        }

        $this->tableGateway->sql = $this->primarySql;
    }

    /**
     * preSelect()
     * Replace adapter with replica temporarily
     */
    public function preSelect(): void
    {
        $this->tableGateway->sql = $this->replicaSql;
    }
}
