<?php

namespace LaminasIntegrationTest\Db\Adapter\Driver\Pdo\Mysql;

use Laminas\Db\Adapter\Adapter;
use LaminasIntegrationTest\Db\Adapter\Driver\Pdo\AbstractAdapterTestCase;

final class AdapterTest extends AbstractAdapterTestCase
{
    use AdapterTrait;

    /** @var Adapter */
    public const DB_SERVER_PORT = 3306;
}
