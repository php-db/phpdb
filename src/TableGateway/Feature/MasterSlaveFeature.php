<?php

declare(strict_types=1);

namespace PhpDb\TableGateway\Feature;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Sql;

/**
 * @deprecated Use PrimaryReplicaFeature instead. This class will be removed in the next major release.
 */
final class MasterSlaveFeature extends PrimaryReplicaFeature
{
    public function __construct(AdapterInterface $slaveAdapter, ?Sql $slaveSql = null)
    {
        parent::__construct($slaveAdapter, $slaveSql);
    }

    /**
     * @deprecated Use PrimaryReplicaFeature::getReplicaAdapter() instead.
     */
    public function getSlaveAdapter(): AdapterInterface
    {
        return $this->getReplicaAdapter();
    }

    /**
     * @deprecated Use PrimaryReplicaFeature::getReplicaSql() instead.
     */
    public function getSlaveSql(): ?Sql
    {
        return $this->getReplicaSql();
    }
}
