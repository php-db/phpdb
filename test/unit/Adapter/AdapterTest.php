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
use PhpDb\Adapter\Exception\InvalidArgumentException;
use PhpDb\Adapter\Exception\RuntimeException;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\Profiler;
use PhpDb\ResultSet\ResultSet;
use PhpDb\ResultSet\ResultSetInterface;
use PhpDbTest\TestAsset\TemporaryResultSet;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Adapter::class, 'setProfiler')]
#[CoversMethod(Adapter::class, 'getProfiler')]
#[CoversMethod(Adapter::class, 'getDriver')]
#[CoversMethod(Adapter::class, 'getPlatform')]
#[CoversMethod(Adapter::class, 'getQueryResultSetPrototype')]
#[CoversMethod(Adapter::class, 'executeQuery')]
#[CoversMethod(Adapter::class, 'getCurrentSchema')]
#[CoversMethod(Adapter::class, 'query')]
#[CoversMethod(Adapter::class, 'createStatement')]
#[CoversMethod(Adapter::class, '__get')]
#[CoversMethod(Adapter::class, '__construct')]
#[CoversMethod(Adapter::class, 'getHelpers')]
#[Group('unit')]
final class AdapterTest extends TestCase
{
    protected DriverInterface&MockObject $mockDriver;

    protected PlatformInterface&MockObject $mockPlatform;

    protected ConnectionInterface&MockObject $mockConnection;

    protected StatementInterface&MockObject $mockStatement;

    protected Adapter $adapter;

    #[Test]
    public function constructorWithProfilerDelegatesToSetProfiler(): void
    {
        $profilerMock = $this->createMock(Profiler\ProfilerInterface::class);
        $driverMock   = $this->createMock(DriverInterface::class);
        $platformMock = $this->createMock(PlatformInterface::class);

        $adapter = new Adapter(
            driver: $driverMock,
            platform: $platformMock,
            profiler: $profilerMock,
        );

        static::assertSame($profilerMock, $adapter->getProfiler());
    }

    #[Test]
    #[TestDox('unit test: Test createStatement() produces a statement object')]
    public function createStatementDelegatesToDriver(): void
    {
        static::assertSame($this->mockStatement, $this->adapter->createStatement());
    }

    #[Test]
    #[TestDox('unit test: Test executeQuery() returns the raw result without wrapping query results')]
    public function executeQueryReturnsRawResultWithoutWrappingQueryResults(): void
    {
        $sql    = 'SELECT foo';
        $result = $this->createMock(ResultInterface::class);

        $this->mockConnection->method('execute')->willReturn($result);
        $result->expects($this->any())->method('isQueryResult')->willReturn(true);
        $result->expects($this->never())->method('getQueryResult');

        static::assertSame($result, $this->adapter->executeQuery($sql));
    }

    #[Test]
    #[TestDox('unit test: Test executeQuery() throws when execution does not produce a result')]
    public function executeQueryThrowsWhenExecutionDoesNotProduceAResult(): void
    {
        $sql = 'SELECT foo';
        $this->mockConnection->method('execute')->willReturn(null);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(RuntimeException::MISSING_QUERY_RESULT);

        $this->adapter->executeQuery($sql);
    }

    #[Test]
    #[TestDox('unit test: Test executeQuery() with raw SQL delegates to connection execute')]
    public function executeQueryWithRawSqlDelegatesToConnectionExecute(): void
    {
        $sql    = 'SELECT foo';
        $result = $this->createMock(ResultInterface::class);
        $this->mockConnection->expects($this->once())->method('execute')->with($sql)->willReturn($result);

        static::assertSame($result, $this->adapter->executeQuery($sql));
    }

    #[Test]
    #[TestDox('unit test: Test executeQuery() with a prepared statement executes the statement')]
    public function executeQueryWithStatementExecutesStatement(): void
    {
        $result = $this->createMock(ResultInterface::class);
        $this->mockStatement->expects($this->once())->method('execute')->willReturn($result);
        $this->mockConnection->expects($this->never())->method('execute');

        static::assertSame($result, $this->adapter->executeQuery($this->mockStatement));
    }

    #[Test]
    #[TestDox('unit test: Test setProfiler() will store profiler')]
    public function fluentSetProfiler(): void
    {
        $ret = $this->adapter->setProfiler(new Profiler\Profiler());
        static::assertSame($this->adapter, $ret);
    }

    #[Test]
    #[TestDox('unit test: Test getCurrentSchema() returns current schema from connection object')]
    public function getCurrentSchemaDelegatesToConnection(): void
    {
        $this->mockConnection->expects($this->any())->method('getCurrentSchema')->willReturn('FooSchema');
        static::assertSame('FooSchema', $this->adapter->getCurrentSchema());
    }

    #[Test]
    #[TestDox('unit test: Test getDriver() will return driver object')]
    public function getDriverReturnsDriver(): void
    {
        static::assertSame($this->mockDriver, $this->adapter->getDriver());
    }

    #[Test]
    public function getHelpersQuoteIdentifierClosureCallsPlatform(): void
    {
        $this->mockPlatform
            ->method('quoteIdentifier')
            ->with('test')
            ->willReturn('"test"');

        $functions = $this->adapter->getHelpers(Adapter::FUNCTION_QUOTE_IDENTIFIER);
        $result    = $functions[0]('test');

        static::assertSame('"test"', $result);
    }

    #[Test]
    public function getHelpersQuoteValueClosureCallsPlatform(): void
    {
        $this->mockPlatform
            ->method('quoteValue')
            ->with('test')
            ->willThrowException(RuntimeException::forVulnerablePlatformQuote('test', 'test'));

        $functions = $this->adapter->getHelpers(Adapter::FUNCTION_QUOTE_VALUE);

        self::expectException(RuntimeException::class);
        $functions[0]('test');
    }

    #[Test]
    public function getHelpersReturnsBothFunctions(): void
    {
        $functions = $this->adapter->getHelpers(
            Adapter::FUNCTION_QUOTE_IDENTIFIER,
            Adapter::FUNCTION_QUOTE_VALUE,
        );

        static::assertCount(2, $functions);
        static::assertIsCallable($functions[0]);
        static::assertIsCallable($functions[1]);
    }

    #[Test]
    public function getHelpersReturnsQuoteIdentifierFunction(): void
    {
        $functions = $this->adapter->getHelpers(Adapter::FUNCTION_QUOTE_IDENTIFIER);

        static::assertCount(1, $functions);
        static::assertIsCallable($functions[0]);
    }

    #[Test]
    public function getHelpersReturnsQuoteValueFunction(): void
    {
        $functions = $this->adapter->getHelpers(Adapter::FUNCTION_QUOTE_VALUE);

        static::assertCount(1, $functions);
        static::assertIsCallable($functions[0]);
    }

    #[Test]
    #[TestDox('unit test: Test getPlatform() returns platform object')]
    public function getPlatformReturnsPlatform(): void
    {
        static::assertSame($this->mockPlatform, $this->adapter->getPlatform());
    }

    #[Test]
    #[TestDox('unit test: Test getProfiler() will store profiler')]
    public function getProfilerReturnsProfiler(): void
    {
        $this->adapter->setProfiler($profiler = new Profiler\Profiler());
        static::assertSame($profiler, $this->adapter->getProfiler());

        $adapter = new Adapter(
            driver: $this->mockDriver,
            platform: $this->mockPlatform,
            profiler: new Profiler\Profiler(),
        );
        static::assertInstanceOf(Profiler\Profiler::class, $adapter->getProfiler());
    }

    #[Test]
    #[TestDox('unit test: Test getPlatform() returns platform object')]
    public function getQueryResultSetPrototypeReturnsResultSet(): void
    {
        static::assertInstanceOf(ResultSetInterface::class, $this->adapter->getQueryResultSetPrototype());
    }

    #[Test]
    public function magicGetReturnsDriverAndPlatformCaseInsensitively(): void
    {
        static::assertSame($this->mockDriver, $this->adapter->driver);
        /** @phpstan-ignore property.notFound */
        static::assertSame($this->mockDriver, $this->adapter->DrivER);
        /** @phpstan-ignore property.notFound */
        static::assertSame($this->mockPlatform, $this->adapter->PlatForm);
        static::assertSame($this->mockPlatform, $this->adapter->platform);

        self::expectException('InvalidArgumentException');
        self::expectExceptionMessage(InvalidArgumentException::INVALID_MAGIC_PROPERTY);
        /** @phpstan-ignore property.notFound, expr.resultUnused */
        $this->adapter->foo;
    }

    #[Test]
    #[TestDox('unit test: Test prepareQuery() binds an array of parameters as a ParameterContainer')]
    public function prepareQueryBindsParameterArray(): void
    {
        $this->mockStatement
            ->expects($this->once())
            ->method('setParameterContainer')
            ->with(static::callback(
                static fn(ParameterContainer $container): bool => $container->getNamedArray() === ['bar' => 'foo'],
            ));

        $this->adapter->prepareQuery('SELECT foo, :bar', ['bar' => 'foo']);
    }

    #[Test]
    #[TestDox('unit test: Test prepareQuery() binds a ParameterContainer directly')]
    public function prepareQueryBindsParameterContainerDirectly(): void
    {
        $parameterContainer = new ParameterContainer(['bar' => 'foo']);

        $this->mockStatement
            ->expects($this->once())
            ->method('setParameterContainer')
            ->with($parameterContainer);

        $this->adapter->prepareQuery('SELECT foo, :bar', $parameterContainer);
    }

    #[Test]
    #[TestDox('unit test: Test prepareQuery() prepares a statement without executing it')]
    public function prepareQueryPreparesStatementWithoutExecuting(): void
    {
        $this->mockStatement->expects($this->once())->method('prepare');
        $this->mockStatement->expects($this->never())->method('execute');

        $statement = $this->adapter->prepareQuery('SELECT foo');

        static::assertSame($this->mockStatement, $statement);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Test]
    #[Group('#210')]
    public function producedResultSetPrototypeIsDifferentForEachQuery(): void
    {
        $statement = $this->createMock(StatementInterface::class);
        $result    = $this->createMock(ResultInterface::class);

        $this->mockDriver->method('createStatement')->willReturn($statement);
        $this->mockStatement->method('execute')->willReturn($result);
        $result->method('isQueryResult')
            ->willReturn(true);
        $result->method('getQueryResult')
            ->willReturnCallback(static fn(): ResultSetInterface => new ResultSet());

        static::assertNotSame(
            $this->adapter->query('SELECT foo', []),
            $this->adapter->query('SELECT foo', []),
        );
    }

    #[Test]
    public function queryThrowsOnInvalidParameterType(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::INCORRECT_FLAG);

        $this->adapter->query('SELECT 1', 'invalid_mode');
    }

    /**
     * @throws \Exception
     */
    #[Test]
    #[TestDox('unit test: Test query() in execute mode produces a driver result object')]
    public function queryWhenExecutedProducesAResult(): void
    {
        $sql    = 'SELECT foo';
        $result = $this->getMockBuilder(ResultInterface::class)->getMock();
        $this->mockConnection->expects($this->any())->method('execute')->with($sql)->willReturn($result);

        $r = $this->adapter->query($sql, AdapterInterface::QUERY_MODE_EXECUTE);
        static::assertSame($result, $r);
    }

    /**
     * @throws \Exception
     */
    #[Test]
    #[TestDox('unit test: Test query() in execute mode produces a resultset object')]
    public function queryWhenExecutedProducesAResultSetObjectWhenResultIsQuery(): void
    {
        $sql = 'SELECT foo';

        $result = $this->getMockBuilder(ResultInterface::class)->getMock();
        $this->mockConnection->expects($this->any())->method('execute')->with($sql)->willReturn($result);
        $result->expects($this->any())->method('isQueryResult')->willReturn(true);
        $result->expects($this->any())
            ->method('getQueryResult')
            ->willReturnCallback(
                static function (?ResultSetInterface $resultPrototype = null): ResultSetInterface {
                    $resultPrototype ??= new ResultSet();

                    return clone $resultPrototype;
                },
            );

        $r = $this->adapter->query($sql, AdapterInterface::QUERY_MODE_EXECUTE);
        static::assertInstanceOf(ResultSet::class, $r);

        $r = $this->adapter->query($sql, AdapterInterface::QUERY_MODE_EXECUTE, new TemporaryResultSet());
        static::assertInstanceOf(TemporaryResultSet::class, $r);
    }

    /**
     * @throws \Exception
     */
    #[Test]
    #[TestDox('unit test: Test query() in prepare mode produces a statement object')]
    public function queryWhenPreparedProducesStatement(): void
    {
        $s = $this->adapter->query('SELECT foo');
        static::assertSame($this->mockStatement, $s);
    }

    /**
     * @throws \Exception
     */
    #[Test]
    #[TestDox('unit test: Test query() in prepare mode, with array of parameters, produces a result object')]
    public function queryWhenPreparedWithParameterArrayProducesResult(): void
    {
        $parray    = ['bar' => 'foo'];
        $sql       = 'SELECT foo, :bar';
        $statement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $result    = $this->getMockBuilder(ResultInterface::class)->getMock();
        $this->mockDriver
            ->expects($this->any())
            ->method('createStatement')
            ->with($sql)
            ->willReturn($statement);
        $this->mockStatement->expects($this->any())->method('execute')->willReturn($result);

        $r = $this->adapter->query($sql, $parray);
        static::assertSame($result, $r);
    }

    /**
     * @throws \Exception
     */
    #[Test]
    #[TestDox('unit test: Test query() in prepare mode, with ParameterContainer, produces a result object')]
    public function queryWhenPreparedWithParameterContainerProducesResult(): void
    {
        $sql                = 'SELECT foo';
        $parameterContainer = $this->getMockBuilder(ParameterContainer::class)->getMock();
        $result             = $this->getMockBuilder(ResultInterface::class)->getMock();
        $this->mockDriver
            ->expects($this->any())
            ->method('createStatement')
            ->with($sql)
            ->willReturn($this->mockStatement);
        $this->mockStatement->expects($this->any())->method('execute')->willReturn($result);
        $result->expects($this->any())->method('isQueryResult')->willReturn(true);
        $result->expects($this->any())->method('getQueryResult')->willReturn(new ResultSet());

        $r = $this->adapter->query($sql, $parameterContainer);
        static::assertInstanceOf(ResultSet::class, $r);
    }

    #[Test]
    public function setProfilerDelegatesToDriverWhenProfilerAware(): void
    {
        $profiler = $this->createMock(Profiler\ProfilerInterface::class);
        $driver   = $this->createMockForIntersectionOfInterfaces(
            [DriverInterface::class, Profiler\ProfilerAwareInterface::class],
        );
        $driver->expects($this->once())->method('setProfiler')->with($profiler);

        $platform = $this->createMock(PlatformInterface::class);
        $adapter  = new Adapter(
            driver: $driver,
            platform: $platform,
        );

        $adapter->setProfiler($profiler);
    }

    /**
     * @throws Exception
     */
    #[Override]
    protected function setUp(): void
    {
        $this->mockDriver     = $this->createMock(DriverInterface::class);
        $this->mockConnection = $this->createMock(ConnectionInterface::class);
        $this->mockDriver->method('checkEnvironment')->willReturn(true);
        $this->mockDriver->method('getConnection')->willReturn($this->mockConnection);
        $this->mockPlatform  = $this->createMock(PlatformInterface::class);
        $this->mockStatement = $this->createMock(StatementInterface::class);
        $this->mockDriver->method('createStatement')->willReturn($this->mockStatement);

        $this->adapter = new Adapter($this->mockDriver, $this->mockPlatform);
    }
}
