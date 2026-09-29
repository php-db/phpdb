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
    #[\PHPUnit\Framework\Attributes\Test]
    public function disconnectIsNoOpWhenNotConnected(): void
    {
        $connection = new TestConnection();

        $result = $connection->disconnect();

        static::assertSame($connection, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function disconnectNullsResourceWhenConnected(): void
    {
        $connection = new TestConnection();
        $connection->connect();

        static::assertTrue($connection->isConnected());

        $connection->disconnect();

        static::assertFalse($connection->isConnected());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getConnectionParametersReturnsEmptyByDefault(): void
    {
        $connection = new TestConnection();

        static::assertSame([], $connection->getConnectionParameters());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getDriverNameReturnsNullByDefault(): void
    {
        $connection = new TestConnection();

        static::assertNull($connection->getDriverName());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getDriverNameReturnsValueWhenSet(): void
    {
        $connection = new TestConnection('sqlite');

        static::assertSame('sqlite', $connection->getDriverName());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getProfilerReturnsNullByDefault(): void
    {
        $connection = new TestConnection();

        static::assertNull($connection->getProfiler());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getResourceAutoConnectsWhenNotConnected(): void
    {
        $connection = new TestConnection();

        static::assertFalse($connection->isConnected());

        $resource = $connection->getResource();

        static::assertTrue($connection->isConnected());
        static::assertSame('fake-resource', $resource);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function inTransactionReturnsFalseByDefault(): void
    {
        $connection = new TestConnection();

        static::assertFalse($connection->inTransaction());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function setConnectionParametersStoresAndReturnsConnection(): void
    {
        $connection = new TestConnection();
        $params     = ['host' => 'localhost', 'port' => 3306];

        $result = $connection->setConnectionParameters($params);

        static::assertSame($connection, $result);
        static::assertSame($params, $connection->getConnectionParameters());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function setProfilerStoresAndReturnsProfiler(): void
    {
        $connection = new TestConnection();
        $profiler   = $this->createMock(ProfilerInterface::class);

        $result = $connection->setProfiler($profiler);

        static::assertSame($connection, $result);
        static::assertSame($profiler, $connection->getProfiler());
    }
}
