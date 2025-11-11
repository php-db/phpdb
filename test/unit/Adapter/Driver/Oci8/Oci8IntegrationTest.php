<?php

namespace LaminasTest\Db\Adapter\Driver\Oci8;

use PhpDb\Adapter\Driver\Oci8\Oci8;
use PhpDb\Adapter\Driver\Oci8\Statement;
use PhpDb\Adapter\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use stdClass;

#[CoversMethod(Oci8::class, 'checkEnvironment')]
#[Group('integration')]
#[Group('integration-oracle')]
class Oci8IntegrationTest extends AbstractIntegrationTestCase
{
    #[Group('integration-oci8')]
    public function testCheckEnvironment(): void
    {
        $sqlserver = new Oci8([]);
        self::assertNull($sqlserver->checkEnvironment());
    }

    public function testCreateStatement(): void
    {
        $driver   = new Oci8([]);
        $resource = oci_connect(
            $this->variables['username'],
            $this->variables['password'],
            $this->variables['hostname']
        );

        $driver->getConnection()->setResource($resource);

        $stmt = $driver->createStatement('SELECT * FROM DUAL');
        self::assertInstanceOf(Statement::class, $stmt);
        $stmt = $driver->createStatement();
        self::assertInstanceOf(Statement::class, $stmt);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('only accepts an SQL string or an oci8 resource');
        $driver->createStatement(new stdClass());
    }
}
