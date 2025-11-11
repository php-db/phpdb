<?php

namespace PhpDbTest\Adapter\Driver\Oci8;

use PhpDb\Adapter\Driver\Oci8\Connection;
use PhpDb\Adapter\Driver\Oci8\Oci8;
use PhpDb\Adapter\Driver\Oci8\Result;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

#[CoversMethod(Connection::class, 'getCurrentSchema')]
#[CoversMethod(Connection::class, 'setResource')]
#[CoversMethod(Connection::class, 'getResource')]
#[CoversMethod(Connection::class, 'connect')]
#[CoversMethod(Connection::class, 'isConnected')]
#[CoversMethod(Connection::class, 'disconnect')]
#[CoversMethod(Connection::class, 'beginTransaction')]
#[CoversMethod(Connection::class, 'commit')]
#[CoversMethod(Connection::class, 'rollback')]
#[CoversMethod(Connection::class, 'execute')]
#[CoversMethod(Connection::class, 'getLastGeneratedValue')]
#[Group('integration')]
#[Group('integration-oracle')]
class ConnectionIntegrationTest extends AbstractIntegrationTestCase
{
    public function testGetCurrentSchema(): void
    {
        $connection = new Connection($this->variables);
        self::assertInternalType('string', $connection->getCurrentSchema());
    }

    public function testSetResource(): never
    {
        $this->markTestIncomplete('edit this');
        /*
        $resource = oci_connect(
            $this->variables['username'],
            $this->variables['password'],
            $this->variables['hostname']
        );

        $connection = new Connection([]);
        self::assertSame($connection, $connection->setResource($resource));

        $connection->disconnect();
        unset($connection);
        unset($resource);
        */
    }

    public function testGetResource(): void
    {
        $connection = new Connection($this->variables);
        $connection->connect();
        self::assertInternalType('resource', $connection->getResource());

        $connection->disconnect();
        unset($connection);
    }

    public function testConnect(): void
    {
        $connection = new Connection($this->variables);
        self::assertSame($connection, $connection->connect());
        self::assertTrue($connection->isConnected());

        $connection->disconnect();
        unset($connection);
    }

    public function testIsConnected(): void
    {
        $connection = new Connection($this->variables);
        self::assertFalse($connection->isConnected());
        self::assertSame($connection, $connection->connect());
        self::assertTrue($connection->isConnected());

        $connection->disconnect();
        unset($connection);
    }

    public function testDisconnect(): void
    {
        $connection = new Connection($this->variables);
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
        $oci8       = new Oci8($this->variables);
        $connection = $oci8->getConnection();

        $result = $connection->execute('SELECT \'foo\' FROM DUAL');
        self::assertInstanceOf(Result::class, $result);
    }

    public function testGetLastGeneratedValue(): never
    {
        $this->markTestIncomplete('Need to create a temporary sequence.');
        /*
        $connection = new Connection($this->variables);
        $connection->getLastGeneratedValue();
        */
    }

    #[Group('laminas3469')]
    public function testConnectReturnsConnectionWhenResourceSet(): never
    {
        $this->markTestIncomplete('edit this');
        /*
        $resource = oci_connect(
            $this->variables['username'],
            $this->variables['password'],
            $this->variables['hostname']
        );

        $connection = new Connection([]);
        $connection->setResource($resource);
        self::assertSame($connection, $connection->connect());

        $connection->disconnect();
        unset($connection);
        unset($resource);
        */
    }
}
