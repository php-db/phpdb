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
                PdoStubDriver::class     => static function (
                    ContainerInterface $container
                ) use ($pdoDriverInterfaceMock): PdoDriverInterface {
                    return $pdoDriverInterfaceMock;
                },
                PlatformInterface::class => static function (
                    ContainerInterface $container
                ) use ($platformMock): PlatformInterface {
                    return $platformMock;
                },
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

    public static function providerValidService(): array
    {
        return [
            ['PhpDb\Adapter\Writer'],
            ['PhpDb\Adapter\Reader'],
        ];
    }

    public static function providerInvalidService(): array
    {
        return [
            ['PhpDb\Adapter\Unknown'],
        ];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[DataProvider('providerValidService')]
    public function testValidService(string $service): void
    {
        $actual = $this->serviceManager->get($service);
        self::assertInstanceOf(AdapterInterface::class, $actual);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[DataProvider('providerInvalidService')]
    public function testInvalidService(string $service): void
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->serviceManager->get($service);
    }

    public function testCanCreateReturnsFalseForEmptyConfig(): void
    {
        $container = new ServiceManager();
        $container->setService('config', []);

        $factory = new AbstractAdapterInterfaceFactory();

        self::assertFalse($factory->canCreate($container, 'PhpDb\Adapter\Writer'));
    }

    public function testInvokeThrowsWhenDriverNotConfigured(): void
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

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('no driver configured');
        $factory($container, 'PhpDb\Adapter\NoDriver');
    }

    public function testGetConfigCachesResult(): void
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

    public function testGetConfigReturnsEmptyWhenContainerHasNoConfig(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('config')->willReturn(false);

        $factory = new AbstractAdapterInterfaceFactory();

        self::assertFalse($factory->canCreate($container, 'anything'));
    }

    public function testInvokeUsesResultSetFromContainer(): void
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

        self::assertInstanceOf(AdapterInterface::class, $adapter);
        self::assertSame($resultSet, $adapter->getQueryResultSetPrototype());
        self::assertSame($profiler, $adapter->getProfiler());
    }
}
