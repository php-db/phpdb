<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\StatementContainerInterface;

interface PreparableSqlInterface
{
    /**
     * @return void
     */
    public function prepareStatement(AdapterInterface $adapter, StatementContainerInterface $statementContainer);
}
