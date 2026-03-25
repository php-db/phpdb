<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver;

use PhpDb\Adapter\Driver\AbstractConnection;
use PhpDb\Adapter\Profiler\ProfilerInterface;
use PhpDbTest\Adapter\Driver\TestAsset\TestConnection;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversMethod(AbstractConnection::class, 'disconnect')]
#[CoversMethod(AbstractConnection::class, 'getConnectionParameters')]
#[CoversMethod(AbstractConnection::class, 'getDriverName')]
#[CoversMethod(AbstractConnection::class, 'getProfiler')]
#[CoversMethod(AbstractConnection::class, 'getResource')]
#[CoversMethod(AbstractConnection::class, 'setConnectionParameters')]
#[CoversMethod(AbstractConnection::class, 'setProfiler')]
#[CoversMethod(AbstractConnection::class, 'inTransaction')]
#[Group('unit')]
final class AbstractConnectionTest extends TestCase
{
    public function testDisconnectNullsResourceWhenConnected(): void
    {
        $connection = new TestConnection();
        $connection->connect();

        self::assertTrue($connection->isConnected());

        $connection->disconnect();

        self::assertFalse($connection->isConnected());
    }

    public function testDisconnectIsNoOpWhenNotConnected(): void
    {
        $connection = new TestConnection();

        $result = $connection->disconnect();

        self::assertSame($connection, $result);
    }

    public function testGetConnectionParametersReturnsEmptyByDefault(): void
    {
        $connection = new TestConnection();

        self::assertSame([], $connection->getConnectionParameters());
    }

    public function testGetDriverNameReturnsValueWhenSet(): void
    {
        $connection = new TestConnection();
        $connection->setConnectionParameters(['driver' => 'sqlite']);

        self::assertNull($connection->getDriverName());
    }

    public function testGetProfilerReturnsNullByDefault(): void
    {
        $connection = new TestConnection();

        self::assertNull($connection->getProfiler());
    }

    public function testSetProfilerStoresAndReturnsProfiler(): void
    {
        $connection = new TestConnection();
        $profiler   = $this->createMock(ProfilerInterface::class);

        $result = $connection->setProfiler($profiler);

        self::assertSame($connection, $result);
        self::assertSame($profiler, $connection->getProfiler());
    }

    public function testGetResourceAutoConnectsWhenNotConnected(): void
    {
        $connection = new TestConnection();

        self::assertFalse($connection->isConnected());

        $resource = $connection->getResource();

        self::assertTrue($connection->isConnected());
        self::assertSame('fake-resource', $resource);
    }

    public function testSetConnectionParametersStoresAndReturnsConnection(): void
    {
        $connection = new TestConnection();
        $params     = ['host' => 'localhost', 'port' => 3306];

        $result = $connection->setConnectionParameters($params);

        self::assertSame($connection, $result);
        self::assertSame($params, $connection->getConnectionParameters());
    }

    public function testInTransactionReturnsFalseByDefault(): void
    {
        $connection = new TestConnection();

        self::assertFalse($connection->inTransaction());
    }
}
