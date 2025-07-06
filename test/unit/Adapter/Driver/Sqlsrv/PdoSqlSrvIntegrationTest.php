<?php

namespace PhpDbTest\Adapter\Driver\Sqlsrv;

use PhpDb\Adapter\Driver\Pdo\Pdo;
use PHPUnit\Framework\Attributes\Group;

#[Group('integration')]
#[Group('integration-sqlserver')]
final class PdoSqlSrvIntegrationTest extends AbstractIntegrationTestCase
{
    /**
     * @return void
     */
    public function testParameterizedQuery()
    {
        if (! isset($this->adapters['pdo_sqlsrv'])) {
            $this->markTestSkipped('pdo_sqlsrv adapter is not found');
        }

        $driver = new Pdo($this->adapters['pdo_sqlsrv']);

        $stmt   = $driver->createStatement('SELECT ? as col_one');
        $result = $stmt->execute(['a']);
        $row    = $result->current();
        $this->assertEquals('a', $row['col_one']);
    }
}
