<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver\Pdo;

use Closure;
use Exception;
use Override;
use PDO;
use PhpDb\Adapter\Driver\Pdo\AbstractPdoConnection;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Exception\InvalidQueryException;
use PhpDb\Adapter\Exception\RuntimeException;
use PhpDb\Adapter\Profiler\ProfilerInterface;
use PhpDbTest\Adapter\Driver\Pdo\TestAsset\SqliteMemoryPdo;
use PhpDbTest\Adapter\Driver\Pdo\TestAsset\TestConnection;
use PhpDbTest\Adapter\Driver\Pdo\TestAsset\TestPdo;
use PhpDbTest\Adapter\Driver\Pdo\TestAsset\UnconnectableConnection;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[CoversMethod(AbstractPdoConnection::class, 'getResource')]
#[CoversMethod(AbstractPdoConnection::class, 'getDsn')]
#[CoversMethod(AbstractPdoConnection::class, 'setDriver')]
#[CoversMethod(AbstractPdoConnection::class, 'setConnectionParameters')]
#[CoversMethod(AbstractPdoConnection::class, 'isConnected')]
#[CoversMethod(AbstractPdoConnection::class, 'execute')]
#[CoversMethod(AbstractPdoConnection::class, 'beginTransaction')]
#[CoversMethod(AbstractPdoConnection::class, 'commit')]
#[CoversMethod(AbstractPdoConnection::class, 'setResource')]
#[CoversMethod(AbstractPdoConnection::class, 'prepare')]
#[CoversMethod(AbstractPdoConnection::class, 'getConnectedResource')]
#[CoversMethod(AbstractPdoConnection::class, 'getDriver')]
#[Group('unit')]
final class ConnectionTest extends TestCase
{
    protected TestConnection $connection;

    /** @return array<string, array{Closure(AbstractPdoConnection): mixed}> */
    public static function connectedOperationProvider(): array
    {
        return [
            'begin transaction' => [static fn(AbstractPdoConnection $c): mixed => $c->beginTransaction()],
            'commit'            => [static fn(AbstractPdoConnection $c): mixed => $c->commit()],
            'execute'           => [static fn(AbstractPdoConnection $c): mixed => $c->execute('SELECT 1')],
        ];
    }

    #[Test]
    public function beginTransactionAutoConnectsWhenNotConnected(): void
    {
        $connection = new TestConnection(['dsn' => 'sqlite::memory:']);

        static::assertFalse($connection->isConnected());

        $connection->beginTransaction();

        static::assertTrue($connection->isConnected());
    }

    #[Test]
    public function commitAutoConnectsWhenNotConnected(): void
    {
        $connection = new TestConnection(new SqliteMemoryPdo());
        $connection->beginTransaction();
        $connection->beginTransaction();
        $connection->disconnect();

        static::assertFalse($connection->isConnected());

        $connection->commit();

        static::assertTrue($connection->isConnected());
    }

    #[Test]
    public function constructorWithArraySetsConnectionParameters(): void
    {
        $params     = ['dsn' => 'sqlite::memory:', 'username' => 'user'];
        $connection = new TestConnection($params);

        static::assertSame($params, $connection->getConnectionParameters());
    }

    #[Test]
    public function constructorWithPdoResourceSetsConnected(): void
    {
        $pdo        = new SqliteMemoryPdo();
        $connection = new TestConnection($pdo);

        static::assertTrue($connection->isConnected());
    }

    #[Test]
    public function executeAutoConnectsWhenNotConnected(): void
    {
        $connection = new TestConnection(['dsn' => 'sqlite::memory:']);
        $driver     = new TestPdo($connection);

        static::assertFalse($connection->isConnected());

        $connection->execute('SELECT 1');

        static::assertTrue($connection->isConnected());
    }

    #[Test]
    public function executeCallsProfilerFinishBeforeThrowingOnError(): void
    {
        $profiler = $this->createMock(ProfilerInterface::class);
        $profiler->expects($this->once())->method('profilerStart')->willReturnSelf();
        $profiler->expects($this->once())->method('profilerFinish')->willReturnSelf();

        $pdo = new SqliteMemoryPdo();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        $connection = new TestConnection($pdo);
        $driver     = new TestPdo($connection);
        $connection->setProfiler($profiler);

        self::expectException(InvalidQueryException::class);
        @$connection->execute('INVALID SQL STATEMENT HERE %%%');
    }

    #[Test]
    public function executeCallsProfilerStartAndFinish(): void
    {
        $profiler = $this->createMock(ProfilerInterface::class);
        $profiler->expects($this->once())->method('profilerStart')->willReturnSelf();
        $profiler->expects($this->once())->method('profilerFinish')->willReturnSelf();

        $pdo        = new SqliteMemoryPdo();
        $connection = new TestConnection($pdo);
        $driver     = new TestPdo($connection);
        $connection->setProfiler($profiler);

        $connection->execute('SELECT 1');
    }

    #[Test]
    public function executeThrowsWhenNoDriverIsSet(): void
    {
        $connection = new TestConnection(new SqliteMemoryPdo());

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(RuntimeException::MISSING_DRIVER);

        $connection->execute('SELECT 1');
    }

    #[Test]
    public function fluentSetDriver(): void
    {
        $driver = $this->createMock(PdoDriverInterface::class);

        $result = $this->connection->setDriver($driver);

        static::assertSame($this->connection, $result);
    }

    /**
     * Test getConnectedDsn returns a DSN string if it has been set
     */
    #[Test]
    public function getDsnReturnsDsnAfterConnect(): void
    {
        $dsn = 'sqlite::memory:';
        $this->connection->setConnectionParameters(['dsn' => $dsn]);
        try {
            $this->connection->connect();
        } catch (Exception) {
        }
        $responseString = $this->connection->getDsn();

        static::assertEquals($dsn, $responseString);
    }

    #[Test]
    public function getDsnThrowsWhenDsnIsNull(): void
    {
        $connection = new TestConnection(['dsn' => 'sqlite::memory:']);
        $connection->connect();

        $reflection = new ReflectionProperty($connection, 'dsn');
        $reflection->setValue($connection, null);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(RuntimeException::MISSING_DSN);

        $connection->getDsn();
    }

    #[Test]
    public function prepareAutoConnectsAndReturnsStatement(): void
    {
        $connection = new TestConnection(['dsn' => 'sqlite::memory:']);
        $driver     = new TestPdo($connection);

        static::assertFalse($connection->isConnected());

        $statement = $connection->prepare('SELECT 1');

        static::assertTrue($connection->isConnected());
        static::assertInstanceOf(Statement::class, $statement);
    }

    #[Test]
    public function prepareConnectsBeforeCreatingTheStatement(): void
    {
        $connection = new TestConnection(['dsn' => 'sqlite::memory:']);
        $driver     = $this->createStub(PdoDriverInterface::class);
        $connection->setDriver($driver);

        $connection->prepare('SELECT 1');

        static::assertTrue($connection->isConnected());
    }

    #[Test]
    public function prepareThrowsWhenNoDriverIsSet(): void
    {
        $connection = new TestConnection(new SqliteMemoryPdo());

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(RuntimeException::MISSING_DRIVER);

        $connection->prepare('SELECT 1');
    }

    /**
     * Test getResource method tries to connect to  the database, it should never return null
     */
    #[Test]
    public function resource(): void
    {
        $resource = $this->connection->getResource();
        static::assertNotNull($resource);
    }

    #[Test]
    public function setConnectionParametersStoresParams(): void
    {
        $params = ['dsn' => 'sqlite::memory:', 'username' => 'test'];

        $this->connection->setConnectionParameters($params);

        static::assertSame($params, $this->connection->getConnectionParameters());
    }

    #[Test]
    public function setResourceConnectsWithTheGivenResource(): void
    {
        $connection = new TestConnection([]);
        $pdo        = new SqliteMemoryPdo();

        static::assertSame($connection, $connection->setResource($pdo));
        static::assertSame($pdo, $connection->getResource());
    }

    #[Test]
    public function setResourceStoresTheLowercaseDriverName(): void
    {
        $pdo = $this->createStub(PDO::class);
        $pdo->method('getAttribute')->willReturn('SQLite');

        $connection = new TestConnection([]);
        $connection->setResource($pdo);

        static::assertSame('sqlite', $connection->getDriverName());
    }

    /** @param Closure(AbstractPdoConnection): mixed $operation */
    #[Test]
    #[DataProvider('connectedOperationProvider')]
    public function throwsWhenConnectingDoesNotProduceAPdoResource(Closure $operation): void
    {
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(RuntimeException::MISSING_CONNECTION_RESOURCE);

        $operation(new UnconnectableConnection());
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->connection = new TestConnection(
            [
                'dsn'      => 'sqlite::memory:',
                'username' => 'bar',
                'password' => 'baz',
            ],
        );
    }
}
