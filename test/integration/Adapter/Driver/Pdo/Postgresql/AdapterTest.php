<?php

namespace PhpDbIntegrationTest\Adapter\Driver\Pdo\Postgresql;

use PhpDbIntegrationTest\Adapter\Driver\Pdo\AbstractAdapterTestCase;
use PhpDbIntegrationTest\Adapter\Driver\Pdo\AdapterTrait as BaseAdapterTrait;

class AdapterTest extends AbstractAdapterTestCase
{
    use AdapterTrait;
    use BaseAdapterTrait;

    public ?int $port = 5432;
}
