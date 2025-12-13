<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver\Pdo;

use PhpDb\Adapter\Driver\Pdo\AbstractPdoConnection;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDbTest\Adapter\Driver\Pdo\TestAsset\TestConnection;
use PhpDbTest\Adapter\Driver\Pdo\TestAsset\TestPdo;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversMethod(AbstractPdoConnection::class, 'getCurrentSchema')]
#[CoversMethod(AbstractPdoConnection::class, 'setResource')]
#[CoversMethod(AbstractPdoConnection::class, 'getResource')]
#[CoversMethod(AbstractPdoConnection::class, 'connect')]
#[CoversMethod(AbstractPdoConnection::class, 'isConnected')]
#[CoversMethod(AbstractPdoConnection::class, 'disconnect')]
#[CoversMethod(AbstractPdoConnection::class, 'beginTransaction')]
#[CoversMethod(AbstractPdoConnection::class, 'commit')]
#[CoversMethod(AbstractPdoConnection::class, 'rollback')]
#[CoversMethod(AbstractPdoConnection::class, 'execute')]
#[CoversMethod(AbstractPdoConnection::class, 'prepare')]
#[CoversMethod(AbstractPdoConnection::class, 'getLastGeneratedValue')]
#[Group('integration')]
#[Group('integration-pdo')]
class ConnectionIntegrationTest extends TestCase
{
    /** @var array<string, string> */
    protected array $variables = ['pdodriver' => 'sqlite', 'database' => ':memory:'];

    public function testGetCurrentSchema(): void
    {
        $connection = new TestConnection($this->variables);
        self::assertIsString($connection->getCurrentSchema());
    }

    public function testSetResource(): void
    {
        $resource   = new TestAsset\SqliteMemoryPdo();
        $connection = new TestConnection([]);
        self::assertSame($connection, $connection->setResource($resource));

        $connection->disconnect();
        unset($connection);
        unset($resource);
    }

    public function testGetResource(): void
    {
        $connection = new TestConnection($this->variables);
        $connection->connect();
        self::assertInstanceOf('PDO', $connection->getResource());

        $connection->disconnect();
        unset($connection);
    }

    public function testConnect(): void
    {
        $connection = new TestConnection($this->variables);
        self::assertSame($connection, $connection->connect());
        self::assertTrue($connection->isConnected());

        $connection->disconnect();
        unset($connection);
    }

    public function testIsConnected(): void
    {
        $connection = new TestConnection($this->variables);
        self::assertFalse($connection->isConnected());
        self::assertSame($connection, $connection->connect());
        self::assertTrue($connection->isConnected());

        $connection->disconnect();
        unset($connection);
    }

    public function testDisconnect(): void
    {
        $connection = new TestConnection($this->variables);
        $connection->connect();
        self::assertTrue($connection->isConnected());
        $connection->disconnect();
        self::assertFalse($connection->isConnected());
    }

    /**
     * @todo   Implement testBeginTransaction().
     */
    public function testBeginTransaction(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo   Implement testCommit().
     */
    public function testCommit(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo   Implement testRollback().
     */
    public function testRollback(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testExecute(): void
    {
        $sqlsrv     = new TestPdo($this->variables);
        $connection = $sqlsrv->getConnection();

        $result = $connection->execute('SELECT \'foo\'');
        self::assertInstanceOf(Result::class, $result);
    }

    public function testPrepare(): void
    {
        $sqlsrv = new TestPdo($this->variables);
        /** @var AbstractPdoConnection $connection */
        $connection = $sqlsrv->getConnection();

        $statement = $connection->prepare('SELECT \'foo\'');
        self::assertInstanceOf(Statement::class, $statement);
    }

    public function testGetLastGeneratedValue(): never
    {
        $this->markTestIncomplete('Need to create a temporary sequence.');
        //$connection = new Connection($this->variables);
        //$connection->getLastGeneratedValue();
    }

    #[Group('laminas3469')]
    public function testConnectReturnsConnectionWhenResourceSet(): void
    {
        $resource   = new TestAsset\SqliteMemoryPdo();
        $connection = new TestConnection([]);
        $connection->setResource($resource);
        self::assertSame($connection, $connection->connect());

        $connection->disconnect();
        unset($connection);
        unset($resource);
    }
}
