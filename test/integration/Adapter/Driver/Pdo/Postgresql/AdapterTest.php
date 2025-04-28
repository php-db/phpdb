<?php

namespace LaminasIntegrationTest\Db\Adapter\Driver\Pdo\Postgresql;

use LaminasIntegrationTest\Db\Adapter\Driver\Pdo\AbstractAdapterTestCase;
use LaminasIntegrationTest\Db\Adapter\Driver\Pdo\AdapterTrait as BaseAdapterTrait;

class AdapterTest extends AbstractAdapterTestCase
{
    use AdapterTrait;
    use BaseAdapterTrait;

    public ?int $port = 5432;
}
