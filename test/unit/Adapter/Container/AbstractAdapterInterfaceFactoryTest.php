<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Container;

use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceManager;
use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Container\AbstractAdapterInterfaceFactory;
use PhpDbTest\TestAsset\PdoStubDriver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

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
}
