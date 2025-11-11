<?php

namespace PhpDbTest\Adapter\Driver\IbmDb2;

use PhpDb\Adapter\Driver\IbmDb2\IbmDb2;
use PhpDb\Adapter\Driver\IbmDb2\Statement;
use PhpDb\Adapter\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use stdClass;

#[CoversMethod(IbmDb2::class, 'checkEnvironment')]
#[Group('integration')]
#[Group('integration-ibm_db2')]
class IbmDb2IntegrationTest extends AbstractIntegrationTestCase
{
    #[Group('integration-ibm_db2')]
    public function testCheckEnvironment(): void
    {
        $ibmdb2 = new IbmDb2([]);
        self::assertNull($ibmdb2->checkEnvironment());
    }

    public function testCreateStatement(): void
    {
        $driver = new IbmDb2([]);

        $resource = db2_connect(
            $this->variables['database'],
            $this->variables['username'],
            $this->variables['password']
        );

        $stmtResource = db2_prepare($resource, 'SELECT 1 FROM SYSIBM.SYSDUMMY1');

        $driver->getConnection()->setResource($resource);

        $stmt = $driver->createStatement('SELECT 1 FROM SYSIBM.SYSDUMMY1');
        self::assertInstanceOf(Statement::class, $stmt);
        $stmt = $driver->createStatement($stmtResource);
        self::assertInstanceOf(Statement::class, $stmt);
        $stmt = $driver->createStatement();
        self::assertInstanceOf(Statement::class, $stmt);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('only accepts an SQL string or an ibm_db2 resource');
        $driver->createStatement(new stdClass());
    }
}
