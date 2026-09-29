<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver;

use PhpDb\Adapter\Driver\AbstractConnection;
use PhpDb\Adapter\Profiler\ProfilerInterface;
use PhpDbTest\Adapter\Driver\TestAsset\TestConnection;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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
    #[Test]
    public function disconnectIsNoOpWhenNotConnected(): void
    {
        $connection = new TestConnection();

        $result = $connection->disconnect();

        static::assertSame($connection, $result);
    }

    #[Test]
    public function disconnectNullsResourceWhenConnected(): void
    {
        $connection = new TestConnection();
        $connection->connect();

        static::assertTrue($connection->isConnected());

        $connection->disconnect();

        static::assertFalse($connection->isConnected());
    }

    #[Test]
    public function getConnectionParametersReturnsEmptyByDefault(): void
    {
        $connection = new TestConnection();

        static::assertSame([], $connection->getConnectionParameters());
    }

    #[Test]
    public function getDriverNameReturnsNullByDefault(): void
    {
        $connection = new TestConnection();

        static::assertNull($connection->getDriverName());
    }

    #[Test]
    public function getDriverNameReturnsValueWhenSet(): void
    {
        $connection = new TestConnection('sqlite');

        static::assertSame('sqlite', $connection->getDriverName());
    }

    #[Test]
    public function getProfilerReturnsNullByDefault(): void
    {
        $connection = new TestConnection();

        static::assertNull($connection->getProfiler());
    }

    #[Test]
    public function getResourceAutoConnectsWhenNotConnected(): void
    {
        $connection = new TestConnection();

        static::assertFalse($connection->isConnected());

        $resource = $connection->getResource();

        static::assertTrue($connection->isConnected());
        static::assertSame('fake-resource', $resource);
    }

    #[Test]
    public function inTransactionReturnsFalseByDefault(): void
    {
        $connection = new TestConnection();

        static::assertFalse($connection->inTransaction());
    }

    #[Test]
    public function setConnectionParametersStoresAndReturnsConnection(): void
    {
        $connection = new TestConnection();
        $params     = ['host' => 'localhost', 'port' => 3306];

        $result = $connection->setConnectionParameters($params);

        static::assertSame($connection, $result);
        static::assertSame($params, $connection->getConnectionParameters());
    }

    #[Test]
    public function setProfilerStoresAndReturnsProfiler(): void
    {
        $connection = new TestConnection();
        $profiler   = $this->createMock(ProfilerInterface::class);

        $result = $connection->setProfiler($profiler);

        static::assertSame($connection, $result);
        static::assertSame($profiler, $connection->getProfiler());
    }
}
