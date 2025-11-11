<?php

namespace LaminasTest\Db\Adapter;

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\Mysqli\Mysqli;
use PhpDb\Adapter\Driver\Pdo\Pdo;
use PhpDb\Adapter\Driver\Pgsql\Pgsql;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\Sqlsrv\Sqlsrv;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\IbmDb2;
use PhpDb\Adapter\Platform\Mysql;
use PhpDb\Adapter\Platform\Oracle;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\Platform\Postgresql;
use PhpDb\Adapter\Platform\Sql92;
use PhpDb\Adapter\Platform\Sqlite;
use PhpDb\Adapter\Platform\SqlServer;
use PhpDb\Adapter\Profiler;
use PhpDb\ResultSet\ResultSet;
use PhpDb\ResultSet\ResultSetInterface;
use LaminasTest\Db\TestAsset\TemporaryResultSet;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function extension_loaded;

#[CoversMethod(Adapter::class, 'setProfiler')]
#[CoversMethod(Adapter::class, 'getProfiler')]
#[CoversMethod(Adapter::class, 'createDriver')]
#[CoversMethod(Adapter::class, 'createPlatform')]
#[CoversMethod(Adapter::class, 'getDriver')]
#[CoversMethod(Adapter::class, 'getPlatform')]
#[CoversMethod(Adapter::class, 'getQueryResultSetPrototype')]
#[CoversMethod(Adapter::class, 'getCurrentSchema')]
#[CoversMethod(Adapter::class, 'query')]
#[CoversMethod(Adapter::class, 'createStatement')]
#[CoversMethod(Adapter::class, '__get')]
class AdapterTest extends TestCase
{
    protected DriverInterface&MockObject $mockDriver;

    protected PlatformInterface&MockObject $mockPlatform;

    protected ConnectionInterface&MockObject $mockConnection;

    protected StatementInterface&MockObject $mockStatement;

    protected Adapter $adapter;

    /**
     * @throws Exception
     */
    #[\Override]
    protected function setUp(): void
    {
        $this->mockDriver     = $this->createMock(DriverInterface::class);
        $this->mockConnection = $this->createMock(ConnectionInterface::class);
        $this->mockDriver->method('checkEnvironment')->willReturn(true);
        $this->mockDriver->method('getConnection')
            ->willReturn($this->mockConnection);
        $this->mockPlatform  = $this->createMock(PlatformInterface::class);
        $this->mockStatement = $this->createMock(StatementInterface::class);
        $this->mockDriver->method('createStatement')
            ->willReturn($this->mockStatement);

        $this->adapter = new Adapter($this->mockDriver, $this->mockPlatform);
    }

    #[TestDox('unit test: Test setProfiler() will store profiler')]
    public function testSetProfiler(): void
    {
        $ret = $this->adapter->setProfiler(new Profiler\Profiler());
        self::assertSame($this->adapter, $ret);
    }

    #[TestDox('unit test: Test getProfiler() will store profiler')]
    public function testGetProfiler(): void
    {
        $this->adapter->setProfiler($profiler = new Profiler\Profiler());
        self::assertSame($profiler, $this->adapter->getProfiler());

        $adapter = new Adapter(['driver' => $this->mockDriver, 'profiler' => true], $this->mockPlatform);
        self::assertInstanceOf(Profiler\Profiler::class, $adapter->getProfiler());
    }

    #[TestDox('unit test: Test createDriverFromParameters() will create proper driver type')]
    public function testCreateDriver(): void
    {
        if (extension_loaded('mysqli')) {
            $adapter = new Adapter(['driver' => 'mysqli'], $this->mockPlatform);
            self::assertInstanceOf(Mysqli::class, $adapter->driver);
            unset($adapter);
        }

        if (extension_loaded('pgsql')) {
            $adapter = new Adapter(['driver' => 'pgsql'], $this->mockPlatform);
            self::assertInstanceOf(Pgsql::class, $adapter->driver);
            unset($adapter);
        }

        if (extension_loaded('sqlsrv')) {
            $adapter = new Adapter(['driver' => 'sqlsrv'], $this->mockPlatform);
            self::assertInstanceOf(Sqlsrv::class, $adapter->driver);
            unset($adapter);
        }

        if (extension_loaded('pdo')) {
            $adapter = new Adapter(['driver' => 'pdo_sqlite'], $this->mockPlatform);
            self::assertInstanceOf(Pdo::class, $adapter->driver);
            unset($adapter);
        }
    }

    #[TestDox('unit test: Test createPlatformFromDriver() will create proper platform from driver')]
    public function testCreatePlatform(): void
    {
        $driver = clone $this->mockDriver;
        $driver->expects($this->any())->method('getDatabasePlatformName')->willReturn('Mysql');
        $adapter = new Adapter($driver);
        self::assertInstanceOf(Mysql::class, $adapter->platform);
        unset($adapter, $driver);

        $driver = clone $this->mockDriver;
        $driver->expects($this->any())->method('getDatabasePlatformName')->willReturn('SqlServer');
        $adapter = new Adapter($driver);
        self::assertInstanceOf(SqlServer::class, $adapter->platform);
        unset($adapter, $driver);

        $driver = clone $this->mockDriver;
        $driver->expects($this->any())->method('getDatabasePlatformName')->willReturn('Postgresql');
        $adapter = new Adapter($driver);
        self::assertInstanceOf(Postgresql::class, $adapter->platform);
        unset($adapter, $driver);

        $driver = clone $this->mockDriver;
        $driver->expects($this->any())->method('getDatabasePlatformName')->willReturn('Sqlite');
        $adapter = new Adapter($driver);
        self::assertInstanceOf(Sqlite::class, $adapter->platform);
        unset($adapter, $driver);

        $driver = clone $this->mockDriver;
        $driver->expects($this->any())->method('getDatabasePlatformName')->willReturn('IbmDb2');
        $adapter = new Adapter($driver);
        self::assertInstanceOf(IbmDb2::class, $adapter->platform);
        unset($adapter, $driver);

        $driver = clone $this->mockDriver;
        $driver->expects($this->any())->method('getDatabasePlatformName')->willReturn('Oracle');
        $adapter = new Adapter($driver);
        self::assertInstanceOf(Oracle::class, $adapter->platform);
        unset($adapter, $driver);

        $driver = clone $this->mockDriver;
        $driver->expects($this->any())->method('getDatabasePlatformName')->willReturn('Foo');
        $adapter = new Adapter($driver);
        self::assertInstanceOf(Sql92::class, $adapter->platform);
        unset($adapter, $driver);

        // ensure platform can created via string, and also that it passed in options to platform object
        $driver  = [
            'driver'           => 'pdo_oci',
            'platform'         => 'Oracle',
            'platform_options' => ['quote_identifiers' => false],
        ];
        $adapter = new Adapter($driver);
        self::assertInstanceOf(Oracle::class, $adapter->platform);
        self::assertEquals('foo', $adapter->getPlatform()->quoteIdentifier('foo'));
        unset($adapter, $driver);
    }

    #[TestDox('unit test: Test getDriver() will return driver object')]
    public function testGetDriver(): void
    {
        self::assertSame($this->mockDriver, $this->adapter->getDriver());
    }

    #[TestDox('unit test: Test getPlatform() returns platform object')]
    public function testGetPlatform(): void
    {
        self::assertSame($this->mockPlatform, $this->adapter->getPlatform());
    }

    #[TestDox('unit test: Test getPlatform() returns platform object')]
    public function testGetQueryResultSetPrototype(): void
    {
        self::assertInstanceOf(ResultSetInterface::class, $this->adapter->getQueryResultSetPrototype());
    }

    #[TestDox('unit test: Test getCurrentSchema() returns current schema from connection object')]
    public function testGetCurrentSchema(): void
    {
        $this->mockConnection->expects($this->any())->method('getCurrentSchema')->willReturn('FooSchema');
        self::assertEquals('FooSchema', $this->adapter->getCurrentSchema());
    }

    /**
     * @throws \Exception
     */
    #[TestDox('unit test: Test query() in prepare mode produces a statement object')]
    public function testQueryWhenPreparedProducesStatement(): void
    {
        $s = $this->adapter->query('SELECT foo');
        self::assertSame($this->mockStatement, $s);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Group('#210')]
    public function testProducedResultSetPrototypeIsDifferentForEachQuery(): void
    {
        $statement = $this->createMock(StatementInterface::class);
        $result    = $this->createMock(ResultInterface::class);

        $this->mockDriver->method('createStatement')
            ->willReturn($statement);
        $this->mockStatement->method('execute')
            ->willReturn($result);
        $result->method('isQueryResult')
            ->willReturn(true);

        self::assertNotSame(
            $this->adapter->query('SELECT foo', []),
            $this->adapter->query('SELECT foo', [])
        );
    }

    /**
     * @throws \Exception
     */
    #[TestDox('unit test: Test query() in prepare mode, with array of parameters, produces a result object')]
    public function testQueryWhenPreparedWithParameterArrayProducesResult(): void
    {
        $parray    = ['bar' => 'foo'];
        $sql       = 'SELECT foo, :bar';
        $statement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $result    = $this->getMockBuilder(ResultInterface::class)->getMock();
        $this->mockDriver->expects($this->any())->method('createStatement')
            ->with($sql)->willReturn($statement);
        $this->mockStatement->expects($this->any())->method('execute')->willReturn($result);

        $r = $this->adapter->query($sql, $parray);
        self::assertSame($result, $r);
    }

    /**
     * @throws \Exception
     */
    #[TestDox('unit test: Test query() in prepare mode, with ParameterContainer, produces a result object')]
    public function testQueryWhenPreparedWithParameterContainerProducesResult(): void
    {
        $sql                = 'SELECT foo';
        $parameterContainer = $this->getMockBuilder(ParameterContainer::class)->getMock();
        $result             = $this->getMockBuilder(ResultInterface::class)->getMock();
        $this->mockDriver->expects($this->any())->method('createStatement')
            ->with($sql)->willReturn($this->mockStatement);
        $this->mockStatement->expects($this->any())->method('execute')->willReturn($result);
        $result->expects($this->any())->method('isQueryResult')->willReturn(true);

        $r = $this->adapter->query($sql, $parameterContainer);
        self::assertInstanceOf(ResultSet::class, $r);
    }

    /**
     * @throws \Exception
     */
    #[TestDox('unit test: Test query() in execute mode produces a driver result object')]
    public function testQueryWhenExecutedProducesAResult(): void
    {
        $sql    = 'SELECT foo';
        $result = $this->getMockBuilder(ResultInterface::class)->getMock();
        $this->mockConnection->expects($this->any())->method('execute')->with($sql)->willReturn($result);

        $r = $this->adapter->query($sql, Adapter::QUERY_MODE_EXECUTE);
        self::assertSame($result, $r);
    }

    /**
     * @throws \Exception
     */
    #[TestDox('unit test: Test query() in execute mode produces a resultset object')]
    public function testQueryWhenExecutedProducesAResultSetObjectWhenResultIsQuery(): void
    {
        $sql = 'SELECT foo';

        $result = $this->getMockBuilder(ResultInterface::class)->getMock();
        $this->mockConnection->expects($this->any())->method('execute')->with($sql)->willReturn($result);
        $result->expects($this->any())->method('isQueryResult')->willReturn(true);

        $r = $this->adapter->query($sql, Adapter::QUERY_MODE_EXECUTE);
        self::assertInstanceOf(ResultSet::class, $r);

        $r = $this->adapter->query($sql, Adapter::QUERY_MODE_EXECUTE, new TemporaryResultSet());
        self::assertInstanceOf(TemporaryResultSet::class, $r);
    }

    #[TestDox('unit test: Test createStatement() produces a statement object')]
    public function testCreateStatement(): void
    {
        self::assertSame($this->mockStatement, $this->adapter->createStatement());
    }

    // @codingStandardsIgnoreStart
    public function test__get(): void
    {
        // @codingStandardsIgnoreEnd
        self::assertSame($this->mockDriver, $this->adapter->driver);
        /** @psalm-suppress UndefinedMagicPropertyFetch */
        self::assertSame($this->mockDriver, $this->adapter->DrivER);
        /** @psalm-suppress UndefinedMagicPropertyFetch */
        self::assertSame($this->mockPlatform, $this->adapter->PlatForm);
        self::assertSame($this->mockPlatform, $this->adapter->platform);

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid magic');
        $this->adapter->foo;
    }
}
