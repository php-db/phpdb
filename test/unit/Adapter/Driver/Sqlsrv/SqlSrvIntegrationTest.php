<?php

namespace LaminasTest\Db\Adapter\Driver\Sqlsrv;

use Laminas\Db\Adapter\Driver\Sqlsrv\Sqlsrv;
use Laminas\Db\Adapter\Driver\Sqlsrv\Statement;
use Laminas\Db\Adapter\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use stdClass;

#[CoversMethod(Sqlsrv::class, 'checkEnvironment')]
#[Group('integration')]
#[Group('integration-sqlserver')]
class SqlSrvIntegrationTest extends AbstractIntegrationTestCase
{
    /** @var Laminas\Db\Adapter\Driver\Sqlsrv\Sqlsrv */
    private Laminas\Db\Adapter\Driver\Sqlsrv\Sqlsrv|Sqlsrv $driver;

    /** @var resource SQL Server Connection */
    private $resource;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->resource = $this->adapters['sqlsrv'];
        $this->driver   = new Sqlsrv($this->resource);
    }

    #[Group('integration-sqlserver')]
    public function testCheckEnvironment(): void
    {
        $sqlserver = new Sqlsrv([]);
        self::assertNull($sqlserver->checkEnvironment());
    }

    public function testCreateStatement(): void
    {
        $stmt = $this->driver->createStatement('SELECT 1');
        $this->assertInstanceOf(Statement::class, $stmt);
        $stmt = $this->driver->createStatement($this->resource);
        $this->assertInstanceOf(Statement::class, $stmt);
        $stmt = $this->driver->createStatement();
        $this->assertInstanceOf(Statement::class, $stmt);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('only accepts an SQL string or a Sqlsrv resource');
        $this->driver->createStatement(new stdClass());
    }

    public function testParameterizedQuery(): void
    {
        $stmt   = $this->driver->createStatement('SELECT ? as col_one');
        $result = $stmt->execute(['a']);
        $row    = $result->current();
        $this->assertEquals('a', $row['col_one']);
    }
}
