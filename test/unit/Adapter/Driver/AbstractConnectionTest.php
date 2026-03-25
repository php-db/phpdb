<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver;

use PhpDb\Adapter\Driver\AbstractConnection;
use PhpDb\Adapter\Profiler\ProfilerInterface;
use PhpDbTest\TestAsset\ConnectionWrapper;
use PhpDbTest\TestAsset\PdoStubDriver;
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
        $connection = new ConnectionWrapper(new PdoStubDriver());

        self::assertTrue($connection->isConnected());

        $connection->disconnect();

        self::assertFalse($connection->isConnected());
    }

    public function testDisconnectIsNoOpWhenNotConnected(): void
    {
        $connection = new ConnectionWrapper();
        $connection->disconnect();

        $result = $connection->disconnect();

        self::assertSame($connection, $result);
    }

    public function testGetConnectionParametersReturnsEmptyByDefault(): void
    {
        $connection = new ConnectionWrapper();

        self::assertSame([], $connection->getConnectionParameters());
    }

    public function testGetDriverNameReturnsDriverAttribute(): void
    {
        $connection = new ConnectionWrapper(new PdoStubDriver());

        self::assertSame('sqlite', $connection->getDriverName());
    }

    public function testGetProfilerReturnsNullByDefault(): void
    {
        $connection = new ConnectionWrapper();

        self::assertNull($connection->getProfiler());
    }

    public function testSetProfilerStoresAndReturnsProfiler(): void
    {
        $connection = new ConnectionWrapper();
        $profiler   = $this->createMock(ProfilerInterface::class);

        $result = $connection->setProfiler($profiler);

        self::assertSame($connection, $result);
        self::assertSame($profiler, $connection->getProfiler());
    }

    public function testGetResourceAutoConnectsWhenNotConnected(): void
    {
        $connection = new ConnectionWrapper();

        $resource = $connection->getResource();

        self::assertNotNull($resource);
    }

    public function testSetConnectionParametersStoresAndReturnsConnection(): void
    {
        $connection = new ConnectionWrapper();
        $params     = ['host' => 'localhost', 'port' => 3306];

        $result = $connection->setConnectionParameters($params);

        self::assertSame($connection, $result);
        self::assertSame($params, $connection->getConnectionParameters());
    }

    public function testInTransactionReturnsFalseByDefault(): void
    {
        $connection = new ConnectionWrapper();

        self::assertFalse($connection->inTransaction());
    }
}
