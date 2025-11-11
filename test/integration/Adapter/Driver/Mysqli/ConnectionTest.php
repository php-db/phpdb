<?php

namespace LaminasIntegrationTest\Db\Adapter\Driver\Mysqli;

use PhpDb\Adapter\Driver\Mysqli\Connection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
#[Group('integration-mysqli')]
class ConnectionTest extends TestCase
{
    use TraitSetup;

    public function testConnectionOk(): void
    {
        $connection = new Connection($this->variables);
        $connection->connect();

        self::assertTrue($connection->isConnected());
        $connection->disconnect();
    }
}
