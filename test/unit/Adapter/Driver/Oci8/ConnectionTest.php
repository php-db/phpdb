<?php

namespace LaminasTest\Db\Adapter\Driver\Oci8;

use PhpDb\Adapter\Driver\Oci8\Connection;
use PhpDb\Adapter\Driver\Oci8\Oci8;
use Override;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Connection::class, 'setDriver')]
#[CoversMethod(Connection::class, 'setConnectionParameters')]
#[CoversMethod(Connection::class, 'getConnectionParameters')]
class ConnectionTest extends TestCase
{
    protected Connection $connection;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[\Override]
    protected function setUp(): void
    {
        $this->connection = new Connection([]);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    #[Override] protected function tearDown(): void
    {
    }

    public function testSetDriver(): void
    {
        self::assertEquals($this->connection, $this->connection->setDriver(new Oci8([])));
    }

    public function testSetConnectionParameters(): void
    {
        self::assertEquals($this->connection, $this->connection->setConnectionParameters([]));
    }

    public function testGetConnectionParameters(): void
    {
        $this->connection->setConnectionParameters(['foo' => 'bar']);
        self::assertEquals(['foo' => 'bar'], $this->connection->getConnectionParameters());
    }
}
