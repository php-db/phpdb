<?php

namespace PhpDbTest\Adapter\Driver\Pgsql;

use PhpDb\Adapter\Driver\Pgsql\Connection;
use PhpDb\Adapter\Exception as AdapterException;
use PhpDb\Adapter\Exception\InvalidArgumentException;
use PhpDb\Adapter\Exception\RuntimeException;
use PhpDbTest\DeprecatedAssertionsTrait;
use Override;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

use function extension_loaded;
use function pg_client_encoding;

use const PGSQL_CONNECT_FORCE_NEW;

#[CoversMethod(Connection::class, 'getResource')]
final class ConnectionTest extends TestCase
{
    use DeprecatedAssertionsTrait;

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
     * Test getResource method if it tries to connect to the database.
     *
     * @return void
     */
    public function testResourceInvalid()
    {
        if (! extension_loaded('pgsql')) {
            $this->markTestSkipped('pgsql extension not loaded');
        }

        // invalid port should lead to the custom error handler throwing
        $conn = new Connection(['socket' => '127.0.0.1', 'port' => 65112]);
        try {
            $conn->getResource();
            $this->fail('should throw');
        } catch (AdapterException\RuntimeException $exc) {
            $this->assertSame(
                'PhpDb\Adapter\Driver\Pgsql\Connection::connect: Unable to connect to database',
                $exc->getMessage()
            );
        }
    }

    /**
     * Test getResource method if it tries to connect to the database.
     *
     * @return void
     */
    public function testResource()
    {
        if (! extension_loaded('pgsql')) {
            $this->markTestSkipped('pgsql extension not loaded');
        }

        try {
            $resource = $this->connection->getResource();
            // connected with empty string
            self::assertIsResource($resource);
        } catch (AdapterException\RuntimeException $exc) {
            // If it throws an exception it has failed to connect
            $this->expectException(RuntimeException::class);
            throw $exc;
        }
    }

    /**
     * Test disconnect method to return instance of ConnectionInterface
     */
    public function testDisconnect(): void
    {
        include_once 'pgsqlMockFunctions.php';
        self::assertSame($this->connection, $this->connection->disconnect());
    }

    #[Group('6760')]
    #[Group('6787')]
    public function testGetConnectionStringEncodeSpecialSymbol(): void
    {
        $connectionParameters = [
            'driver'   => 'pgsql',
            'host'     => 'localhost',
            'post'     => '5432',
            'dbname'   => 'test',
            'username' => 'test',
            'password' => 'test123!',
        ];

        $this->connection->setConnectionParameters($connectionParameters);

        $getConnectionString = new ReflectionMethod(
            Connection::class,
            'getConnectionString'
        );

        /** @psalm-suppress UnusedMethodCall */
        $getConnectionString->setAccessible(true);

        self::assertEquals(
            'host=localhost user=test password=test123! dbname=test',
            $getConnectionString->invoke($this->connection)
        );
    }

    /**
     * @return void
     */
    public function testSetConnectionTypeException()
    {
        if (! extension_loaded('pgsql')) {
            $this->markTestSkipped('pgsql extension not loaded');
        }

        $this->expectException(InvalidArgumentException::class);
        $this->connection->setType(3);
    }

    /**
     * Test the connection type setter
     *
     * @return void
     */
    public function testSetConnectionType()
    {
        if (! extension_loaded('pgsql')) {
            $this->markTestSkipped('pgsql extension not loaded');
        }
        $type = PGSQL_CONNECT_FORCE_NEW;
        $this->connection->setType($type);
        self::assertEquals($type, self::readAttribute($this->connection, 'type'));
    }

    /**
     * @return void
     */
    #[RunInSeparateProcess]
    public function testSetCharset()
    {
        if (! extension_loaded('pgsql')) {
            $this->markTestSkipped('pgsql extension not loaded');
        }

        $this->connection->setConnectionParameters([
            'driver'   => 'pgsql',
            'host'     => 'localhost',
            'post'     => '5432',
            'dbname'   => 'laminasdb_test',
            'username' => 'postgres',
            'password' => 'postgres',
            'charset'  => 'SQL_ASCII',
        ]);

        try {
            $this->connection->connect();
        } catch (AdapterException\RuntimeException) {
            $this->markTestSkipped('Skipping pgsql charset test due to inability to connecto to database');
        }

        self::assertEquals('SQL_ASCII', pg_client_encoding($this->connection->getResource()));
    }

    /**
     * @return void
     */
    #[RunInSeparateProcess]
    public function testSetInvalidCharset()
    {
        if (! extension_loaded('pgsql')) {
            $this->markTestSkipped('pgsql extension not loaded');
        }

        $this->expectException(RuntimeException::class);

        $this->connection->setConnectionParameters([
            'driver'   => 'pgsql',
            'host'     => 'localhost',
            'post'     => '5432',
            'dbname'   => 'laminasdb_test',
            'username' => 'postgres',
            'password' => 'postgres',
            'charset'  => 'FOOBAR',
        ]);

        $this->connection->connect();
    }
}
