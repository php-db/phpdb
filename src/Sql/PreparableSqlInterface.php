<?php

namespace Laminas\Db\Sql;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Driver\StatementInterface;

interface PreparableSqlInterface
{
    /**
     * @return StatementInterface
     */
    public function prepareStatement(AdapterInterface $adapter, StatementInterface $statementContainer): StatementInterface;
}
