<?php

declare(strict_types=1);

namespace PhpDbTest\Container;

use Laminas\ServiceManager\ServiceManager;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\Profiler\ProfilerInterface;
use PhpDb\Container\AdapterInterfaceFactory;
use PhpDb\Exception\ContainerException;
use PhpDb\ResultSet\ResultSet;
use PhpDb\ResultSet\ResultSetInterface;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[CoversMethod(AdapterInterfaceFactory::class, '__invoke')]
final class AdapterInterfaceFactoryTest extends TestCase
{
    #[Test]
    public function invokeCreatesAdapterWithAllDependencies(): void
    {
        $driverMock   = $this->createMock(DriverInterface::class);
        $platformMock = $this->createMock(PlatformInterface::class);
        $profilerMock = $this->createMock(ProfilerInterface::class);
        $resultSet    = new ResultSet();

        $container = new ServiceManager([
            'factories' => [
                DriverInterface::class   => static fn() => $driverMock,
                PlatformInterface::class => static fn() => $platformMock,
                ProfilerInterface::class => static fn() => $profilerMock,
            ],
        ]);
        $container->setService('config', [
            AdapterInterface::class => [
                'driver' => DriverInterface::class,
            ],
        ]);
        $container->setService(ResultSetInterface::class, $resultSet);

        $factory = new AdapterInterfaceFactory();
        $adapter = $factory($container, AdapterInterface::class);

        static::assertInstanceOf(Adapter::class, $adapter);
    }

    #[Test]
    public function invokeCreatesAdapterWithDefaultResultSet(): void
    {
        $driverMock   = $this->createMock(DriverInterface::class);
        $platformMock = $this->createMock(PlatformInterface::class);

        $container = new ServiceManager([
            'factories' => [
                DriverInterface::class   => static fn() => $driverMock,
                PlatformInterface::class => static fn() => $platformMock,
            ],
        ]);
        $container->setService('config', [
            AdapterInterface::class => [
                'driver' => DriverInterface::class,
            ],
        ]);

        $factory = new AdapterInterfaceFactory();
        $adapter = $factory($container, AdapterInterface::class);

        static::assertInstanceOf(ResultSet::class, $adapter->getQueryResultSetPrototype());
    }

    #[Test]
    public function invokeCreatesAdapterWithoutOptionalProfiler(): void
    {
        $driverMock   = $this->createMock(DriverInterface::class);
        $platformMock = $this->createMock(PlatformInterface::class);

        $container = new ServiceManager([
            'factories' => [
                DriverInterface::class   => static fn() => $driverMock,
                PlatformInterface::class => static fn() => $platformMock,
            ],
        ]);
        $container->setService('config', [
            AdapterInterface::class => [
                'driver' => DriverInterface::class,
            ],
        ]);

        $factory = new AdapterInterfaceFactory();
        $adapter = $factory($container, AdapterInterface::class);

        static::assertInstanceOf(Adapter::class, $adapter);
        static::assertNull($adapter->getProfiler());
    }

    #[Test]
    public function invokeThrowsWhenAdapterConfigIsEmpty(): void
    {
        $driverMock   = $this->createMock(DriverInterface::class);
        $platformMock = $this->createMock(PlatformInterface::class);

        $container = new ServiceManager([
            'factories' => [
                DriverInterface::class   => static fn() => $driverMock,
                PlatformInterface::class => static fn() => $platformMock,
            ],
        ]);
        $container->setService('config', []);

        $factory = new AdapterInterfaceFactory();

        self::expectException(ContainerException::class);
        self::expectExceptionMessage('No configuration found for');
        $factory($container, AdapterInterface::class);
    }

    #[Test]
    public function invokeThrowsWhenContainerHasNoConfig(): void
    {
        $driverMock   = $this->createMock(DriverInterface::class);
        $platformMock = $this->createMock(PlatformInterface::class);

        $container = new ServiceManager([
            'factories' => [
                DriverInterface::class   => static fn() => $driverMock,
                PlatformInterface::class => static fn() => $platformMock,
            ],
        ]);

        $factory = new AdapterInterfaceFactory();

        self::expectException(ContainerException::class);
        self::expectExceptionMessage(ContainerException::MISSING_CONFIG_SERVICE);
        $factory($container, AdapterInterface::class);
    }
}
