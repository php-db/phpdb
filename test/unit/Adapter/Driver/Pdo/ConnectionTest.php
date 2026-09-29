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

    #[\PHPUnit\Framework\Attributes\Test]
    public function beginTransactionAutoConnectsWhenNotConnected(): void
    {
        $connection = new TestConnection(['dsn' => 'sqlite::memory:']);

        static::assertFalse($connection->isConnected());

        $connection->beginTransaction();

        static::assertTrue($connection->isConnected());
    }

    #[\PHPUnit\Framework\Attributes\Test]
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

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorWithArraySetsConnectionParameters(): void
    {
        $params     = ['dsn' => 'sqlite::memory:', 'username' => 'user'];
        $connection = new TestConnection($params);

        static::assertSame($params, $connection->getConnectionParameters());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorWithPdoResourceSetsConnected(): void
    {
        $pdo        = new SqliteMemoryPdo();
        $connection = new TestConnection($pdo);

        static::assertTrue($connection->isConnected());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function executeAutoConnectsWhenNotConnected(): void
    {
        $connection = new TestConnection(['dsn' => 'sqlite::memory:']);
        $driver     = new TestPdo($connection);

        static::assertFalse($connection->isConnected());

        $connection->execute('SELECT 1');

        static::assertTrue($connection->isConnected());
    }

    #[\PHPUnit\Framework\Attributes\Test]
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

    #[\PHPUnit\Framework\Attributes\Test]
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

    #[\PHPUnit\Framework\Attributes\Test]
    public function fluentSetDriver(): void
    {
        $driver = $this->createMock(PdoDriverInterface::class);

        $result = $this->connection->setDriver($driver);

        static::assertSame($this->connection, $result);
    }

    /**
     * Test getConnectedDsn returns a DSN string if it has been set
     */
    #[\PHPUnit\Framework\Attributes\Test]
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

    #[\PHPUnit\Framework\Attributes\Test]
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

    #[\PHPUnit\Framework\Attributes\Test]
    public function prepareAutoConnectsAndReturnsStatement(): void
    {
        $connection = new TestConnection(['dsn' => 'sqlite::memory:']);
        $driver     = new TestPdo($connection);

        static::assertFalse($connection->isConnected());

        $statement = $connection->prepare('SELECT 1');

        static::assertTrue($connection->isConnected());
        static::assertInstanceOf(Statement::class, $statement);
    }

    /**
     * Test getResource method tries to connect to  the database, it should never return null
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function resource(): void
    {
        $resource = $this->connection->getResource();
        static::assertNotNull($resource);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function setConnectionParametersStoresParams(): void
    {
        $params = ['dsn' => 'sqlite::memory:', 'username' => 'test'];

        $this->connection->setConnectionParameters($params);

        static::assertSame($params, $this->connection->getConnectionParameters());
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
