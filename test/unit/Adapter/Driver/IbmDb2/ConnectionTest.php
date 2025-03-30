<?php

namespace LaminasTest\Db\Adapter\Driver\IbmDb2;

use Laminas\Db\Adapter\Driver\IbmDb2\Connection;
use Laminas\Db\Adapter\Driver\IbmDb2\IbmDb2;
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
    protected function setUp(): void
    {
        $this->connection = new Connection([]);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testSetDriver(): void
    {
        self::assertEquals($this->connection, $this->connection->setDriver(new IbmDb2([])));
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
