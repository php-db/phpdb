<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter;

use Override;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\Profiler;
use PhpDb\ResultSet\ResultSet;
use PhpDb\ResultSet\ResultSetInterface;
use PhpDbTest\TestAsset\TemporaryResultSet;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
final class AdapterTest extends TestCase
{
    protected DriverInterface&MockObject $mockDriver;

    protected PlatformInterface&MockObject $mockPlatform;

    protected ConnectionInterface&MockObject $mockConnection;

    protected StatementInterface&MockObject $mockStatement;

    protected Adapter $adapter;

    /**
     * @throws Exception
     */
    #[Override]
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

        $adapter = new Adapter(
            driver: $this->mockDriver,
            platform: $this->mockPlatform,
            profiler: new Profiler\Profiler(),
        );
        self::assertInstanceOf(Profiler\Profiler::class, $adapter->getProfiler());
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

        $r = $this->adapter->query($sql, AdapterInterface::QUERY_MODE_EXECUTE);
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

        $r = $this->adapter->query($sql, AdapterInterface::QUERY_MODE_EXECUTE);
        self::assertInstanceOf(ResultSet::class, $r);

        $r = $this->adapter->query($sql, AdapterInterface::QUERY_MODE_EXECUTE, new TemporaryResultSet());
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
        /** @phpstan-ignore property.notFound */
        self::assertSame($this->mockDriver, $this->adapter->DrivER);
        /** @phpstan-ignore property.notFound */
        self::assertSame($this->mockPlatform, $this->adapter->PlatForm);
        self::assertSame($this->mockPlatform, $this->adapter->platform);

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid magic');
        /** @phpstan-ignore property.notFound, expr.resultUnused */
        $this->adapter->foo;
    }
}
