<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver\Pdo;

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
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[CoversMethod(AbstractPdoConnection::class, 'getResource')]
#[CoversMethod(AbstractPdoConnection::class, 'getDsn')]
#[CoversMethod(AbstractPdoConnection::class, '__construct')]
#[CoversMethod(AbstractPdoConnection::class, 'setDriver')]
#[CoversMethod(AbstractPdoConnection::class, 'setConnectionParameters')]
#[CoversMethod(AbstractPdoConnection::class, 'isConnected')]
#[CoversMethod(AbstractPdoConnection::class, 'execute')]
#[CoversMethod(AbstractPdoConnection::class, 'beginTransaction')]
#[CoversMethod(AbstractPdoConnection::class, 'commit')]
#[CoversMethod(AbstractPdoConnection::class, 'setResource')]
#[CoversMethod(AbstractPdoConnection::class, 'prepare')]
#[Group('unit')]
final class ConnectionTest extends TestCase
{
    protected TestConnection $connection;

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
            ]
        );
    }

    /**
     * Test getResource method tries to connect to  the database, it should never return null
     */
    public function testResource(): void
    {
        $resource = $this->connection->getResource();
        self::assertNotNull($resource);
    }

    /**
     * Test getConnectedDsn returns a DSN string if it has been set
     */
    public function testGetDsnReturnsDsnAfterConnect(): void
    {
        $dsn = "sqlite::memory:";
        $this->connection->setConnectionParameters(['dsn' => $dsn]);
        try {
            $this->connection->connect();
        } catch (Exception) {
        }
        $responseString = $this->connection->getDsn();

        self::assertEquals($dsn, $responseString);
    }

    public function testConstructorWithPdoResourceSetsConnected(): void
    {
        $pdo        = new SqliteMemoryPdo();
        $connection = new TestConnection($pdo);

        self::assertTrue($connection->isConnected());
    }

    public function testConstructorWithArraySetsConnectionParameters(): void
    {
        $params     = ['dsn' => 'sqlite::memory:', 'username' => 'user'];
        $connection = new TestConnection($params);

        self::assertSame($params, $connection->getConnectionParameters());
    }

    public function testFluentSetDriver(): void
    {
        $driver = $this->createMock(PdoDriverInterface::class);

        $result = $this->connection->setDriver($driver);

        self::assertSame($this->connection, $result);
    }

    public function testSetConnectionParametersStoresParams(): void
    {
        $params = ['dsn' => 'sqlite::memory:', 'username' => 'test'];

        $this->connection->setConnectionParameters($params);

        self::assertSame($params, $this->connection->getConnectionParameters());
    }

    public function testExecuteCallsProfilerStartAndFinish(): void
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

    public function testExecuteCallsProfilerFinishBeforeThrowingOnError(): void
    {
        $profiler = $this->createMock(ProfilerInterface::class);
        $profiler->expects($this->once())->method('profilerStart')->willReturnSelf();
        $profiler->expects($this->once())->method('profilerFinish')->willReturnSelf();

        $pdo = new SqliteMemoryPdo();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        $connection = new TestConnection($pdo);
        $driver     = new TestPdo($connection);
        $connection->setProfiler($profiler);

        $this->expectException(InvalidQueryException::class);
        @$connection->execute('INVALID SQL STATEMENT HERE %%%');
    }

    public function testGetDsnThrowsWhenDsnIsNull(): void
    {
        $connection = new TestConnection(['dsn' => 'sqlite::memory:']);
        $connection->connect();

        $reflection = new ReflectionProperty($connection, 'dsn');
        $reflection->setValue($connection, null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The DSN has not been set');

        $connection->getDsn();
    }

    public function testBeginTransactionAutoConnectsWhenNotConnected(): void
    {
        $connection = new TestConnection(['dsn' => 'sqlite::memory:']);

        self::assertFalse($connection->isConnected());

        $connection->beginTransaction();

        self::assertTrue($connection->isConnected());
    }

    public function testCommitAutoConnectsWhenNotConnected(): void
    {
        $connection = new TestConnection(new SqliteMemoryPdo());
        $connection->beginTransaction();
        $connection->beginTransaction();
        $connection->disconnect();

        self::assertFalse($connection->isConnected());

        $connection->commit();

        self::assertTrue($connection->isConnected());
    }

    public function testExecuteAutoConnectsWhenNotConnected(): void
    {
        $connection = new TestConnection(['dsn' => 'sqlite::memory:']);
        $driver     = new TestPdo($connection);

        self::assertFalse($connection->isConnected());

        $connection->execute('SELECT 1');

        self::assertTrue($connection->isConnected());
    }

    public function testPrepareAutoConnectsAndReturnsStatement(): void
    {
        $connection = new TestConnection(['dsn' => 'sqlite::memory:']);
        $driver     = new TestPdo($connection);

        self::assertFalse($connection->isConnected());

        $statement = $connection->prepare('SELECT 1');

        self::assertTrue($connection->isConnected());
        self::assertInstanceOf(Statement::class, $statement);
    }
}
