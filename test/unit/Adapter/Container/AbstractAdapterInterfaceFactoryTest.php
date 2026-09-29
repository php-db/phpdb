<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Container;

use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceManager;
use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\Profiler\ProfilerInterface;
use PhpDb\Container\AbstractAdapterInterfaceFactory;
use PhpDb\Exception\ContainerException;
use PhpDb\ResultSet\ResultSet;
use PhpDb\ResultSet\ResultSetInterface;
use PhpDbTest\TestAsset\PdoStubDriver;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

#[Group('unit')]
#[CoversMethod(AbstractAdapterInterfaceFactory::class, 'canCreate')]
#[CoversMethod(AbstractAdapterInterfaceFactory::class, '__invoke')]
#[CoversMethod(AbstractAdapterInterfaceFactory::class, 'getConfig')]
final class AbstractAdapterInterfaceFactoryTest extends TestCase
{
    private ContainerInterface|ServiceManager $serviceManager;

    public static function providerInvalidService(): array
    {
        return [
            ['PhpDb\Adapter\Unknown'],
        ];
    }

    public static function providerValidService(): array
    {
        return [
            ['PhpDb\Adapter\Writer'],
            ['PhpDb\Adapter\Reader'],
        ];
    }

    #[Test]
    public function canCreateReturnsFalseForEmptyConfig(): void
    {
        $container = new ServiceManager();
        $container->setService('config', []);

        $factory = new AbstractAdapterInterfaceFactory();

        static::assertFalse($factory->canCreate($container, 'PhpDb\Adapter\Writer'));
    }

    #[Test]
    public function getConfigCachesResult(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())
            ->method('has')
            ->with('config')
            ->willReturn(true);
        $container->expects(self::once())
            ->method('get')
            ->with('config')
            ->willReturn([]);

        $factory = new AbstractAdapterInterfaceFactory();

        $factory->canCreate($container, 'anything');
        $factory->canCreate($container, 'anything');
    }

    #[Test]
    public function getConfigReturnsEmptyWhenContainerHasNoConfig(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('config')->willReturn(false);

        $factory = new AbstractAdapterInterfaceFactory();

        static::assertFalse($factory->canCreate($container, 'anything'));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[DataProvider('providerInvalidService')]
    public function invalidService(string $service): void
    {
        self::expectException(ServiceNotFoundException::class);
        $this->serviceManager->get($service);
    }

    #[Test]
    public function invokeThrowsWhenDriverNotConfigured(): void
    {
        $container = new ServiceManager();
        $container->setService('config', [
            AdapterInterface::class => [
                'adapters' => [
                    'PhpDb\Adapter\NoDriver' => [],
                ],
            ],
        ]);

        $factory = new AbstractAdapterInterfaceFactory();
        $factory->canCreate($container, 'PhpDb\Adapter\NoDriver');

        self::expectException(ContainerException::class);
        self::expectExceptionMessage(ContainerException::MISSING_DRIVER);
        $factory($container, 'PhpDb\Adapter\NoDriver');
    }

    #[Test]
    public function invokeUsesResultSetFromContainer(): void
    {
        $resultSet = new ResultSet();
        $profiler  = $this->createMock(ProfilerInterface::class);

        /** @var PdoDriverInterface&MockObject $driverMock */
        $driverMock = $this->createMock(PdoDriverInterface::class);
        /** @var PlatformInterface&MockObject $platformMock */
        $platformMock = $this->createMock(PlatformInterface::class);

        $container = new ServiceManager([
            'abstract_factories' => [AbstractAdapterInterfaceFactory::class],
            'factories'          => [
                PdoStubDriver::class      => static fn() => $driverMock,
                PlatformInterface::class  => static fn() => $platformMock,
                ResultSetInterface::class => static fn() => $resultSet,
                ProfilerInterface::class  => static fn() => $profiler,
            ],
        ]);

        $container->setService('config', [
            AdapterInterface::class => [
                'adapters' => [
                    'MyAdapter' => [
                        'driver' => PdoStubDriver::class,
                    ],
                ],
            ],
        ]);

        $adapter = $container->get('MyAdapter');

        static::assertInstanceOf(AdapterInterface::class, $adapter);
        static::assertSame($resultSet, $adapter->getQueryResultSetPrototype());
        static::assertSame($profiler, $adapter->getProfiler());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[DataProvider('providerValidService')]
    public function validService(string $service): void
    {
        $actual = $this->serviceManager->get($service);
        static::assertInstanceOf(AdapterInterface::class, $actual);
    }

    #[Override]
    protected function setUp(): void
    {
        /** @var PdoDriverInterface&MockObject $pdoDriverInterfaceMock */
        $pdoDriverInterfaceMock = $this->getMockBuilder(PdoDriverInterface::class)->getMock();
        /** @var PlatformInterface&MockObject $platformMock */
        $platformMock = $this->getMockBuilder(PlatformInterface::class)->getMock();

        $config = [
            'abstract_factories' => [AbstractAdapterInterfaceFactory::class],
            'factories'          => [
                PdoStubDriver::class     => static fn(
                    ContainerInterface $container,
                ) => $pdoDriverInterfaceMock,
                PlatformInterface::class => static fn(
                    ContainerInterface $container,
                ) => $platformMock,
            ],
        ];

        $this->serviceManager = new ServiceManager($config);

        $this->serviceManager->setService('config', [
            AdapterInterface::class => [
                'adapters' => [
                    'PhpDb\Adapter\Writer' => [
                        'driver' => PdoStubDriver::class,
                    ],
                    'PhpDb\Adapter\Reader' => [
                        'driver' => PdoStubDriver::class,
                    ],
                ],
            ],
        ]);
    }
}
