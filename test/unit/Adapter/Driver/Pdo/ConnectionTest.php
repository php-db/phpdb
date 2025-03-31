<?php

namespace LaminasTest\Db\Adapter\Driver\Pdo;

use Exception;
use Laminas\Db\Adapter\Driver\Pdo\Connection;
use Laminas\Db\Adapter\Exception\InvalidConnectionParametersException;
use Override;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Connection::class, 'getResource')]
#[CoversMethod(Connection::class, 'getDsn')]
final class ConnectionTest extends TestCase
{
    protected Connection $connection;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->connection = new Connection();
    }

    /**
     * Test getResource method tries to connect to  the database, it should never return null
     */
    public function testResource(): void
    {
        $this->expectException(InvalidConnectionParametersException::class);
        $this->connection->getResource();
    }

    /**
     * Test getConnectedDsn returns a DSN string if it has been set
     */
    public function testGetDsn(): void
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

    #[Group('2622')]
    public function testArrayOfConnectionParametersCreatesCorrectDsn(): void
    {
        $this->connection->setConnectionParameters([
            'driver'      => 'pdo_mysql',
            'charset'     => 'utf8',
            'dbname'      => 'foo',
            'port'        => '3306',
            'unix_socket' => '/var/run/mysqld/mysqld.sock',
        ]);
        try {
            $this->connection->connect();
        } catch (Exception) {
        }
        $responseString = $this->connection->getDsn();

        self::assertStringStartsWith('mysql:', $responseString);
        self::assertStringContainsString('charset=utf8', $responseString);
        self::assertStringContainsString('dbname=foo', $responseString);
        self::assertStringContainsString('port=3306', $responseString);
        self::assertStringContainsString('unix_socket=/var/run/mysqld/mysqld.sock', $responseString);
    }

    public function testHostnameAndUnixSocketThrowsInvalidConnectionParametersException(): void
    {
        $this->expectException(InvalidConnectionParametersException::class);
        $this->expectExceptionMessage(
            'Ambiguous connection parameters, both hostname and unix_socket parameters were set'
        );

        $this->connection->setConnectionParameters([
            'driver'      => 'pdo_mysql',
            'host'        => '127.0.0.1',
            'dbname'      => 'foo',
            'port'        => '3306',
            'unix_socket' => '/var/run/mysqld/mysqld.sock',
        ]);
        $this->connection->connect();
    }

    public function testDblibArrayOfConnectionParametersCreatesCorrectDsn(): void
    {
        $this->connection->setConnectionParameters([
            'driver'  => 'pdo_dblib',
            'charset' => 'UTF-8',
            'dbname'  => 'foo',
            'port'    => '1433',
            'version' => '7.3',
        ]);
        try {
            $this->connection->connect();
        } catch (Exception) {
        }
        $responseString = $this->connection->getDsn();

        $this->assertStringStartsWith('dblib:', $responseString);
        $this->assertStringContainsString('charset=UTF-8', $responseString);
        $this->assertStringContainsString('dbname=foo', $responseString);
        $this->assertStringContainsString('port=1433', $responseString);
        $this->assertStringContainsString('version=7.3', $responseString);
    }
}
