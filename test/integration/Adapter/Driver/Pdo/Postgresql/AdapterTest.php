<?php

namespace LaminasIntegrationTest\Db\Adapter\Driver\Pdo\Postgresql;

use Laminas\Db\Adapter\Adapter;
use LaminasIntegrationTest\Db\Adapter\Driver\Pdo\AbstractAdapterTestCase;

final class AdapterTest extends AbstractAdapterTestCase
{
    use AdapterTrait;

    /** @var Adapter */
    protected $adapter;
    public const DB_SERVER_PORT = 5432;
}
