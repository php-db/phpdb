<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Container;

use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceManager;
use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Platform\Sql92;
use PhpDb\Container\AdapterAbstractServiceFactory;
use PhpDb\Container\ConnectionInterfaceFactoryFactoryInterface;
use PhpDb\Container\DriverInterfaceFactoryFactoryInterface;
use PhpDb\Container\FactoryFactoryInterface;
use PhpDb\Container\PlatformInterfaceFactoryFactoryInterface;
use PhpDbTest\TestAsset\ConnectionWrapper;
use PhpDbTest\TestAsset\PdoStubDriver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class AdapterAbstractServiceFactoryTest extends TestCase
{
    private ServiceManager|ContainerInterface $serviceManager;

    #[Override]
    protected function setUp(): void
    {
        /** @var PdoDriverInterface&MockObject $pdoDriverInterfaceMock */
        $pdoDriverInterfaceMock = $this->getMockBuilder(PdoDriverInterface::class)->getMock();

        $config = [
            'abstract_factories' => [AdapterAbstractServiceFactory::class],
            'aliases'            => [
                ConnectionInterfaceFactoryFactoryInterface::class => 'ConnectionInterfaceFactoryFactory',
                DriverInterfaceFactoryFactoryInterface::class     => 'DriverInterfaceFactoryFactory',
                PlatformInterfaceFactoryFactoryInterface::class   => 'PlatformInterfaceFactoryFactory',
            ],
            'factories'          => [
                'ConnectionInterfaceFactoryFactory'
                    => new class implements ConnectionInterfaceFactoryFactoryInterface
                        {
                        public function __invoke(): callable
                        {
                            return new class implements FactoryFactoryInterface {
                                public function __invoke(): callable
                                {
                                    return new self();
                                }

                                public static function createFromConfig(): ConnectionWrapper
                                {
                                    return new ConnectionWrapper();
                                }
                            };
                        }
                    },
                'DriverInterfaceFactoryFactory'
                    => new class ($pdoDriverInterfaceMock) implements DriverInterfaceFactoryFactoryInterface
                        {
                        private static PdoDriverInterface $pdoDriverInterface;

                        public function __construct(PdoDriverInterface $pdoDriverInterfaceMock)
                        {
                            static::$pdoDriverInterface = $pdoDriverInterfaceMock;
                        }

                        public function __invoke(): callable
                        {
                            return new class (static::$pdoDriverInterface) implements FactoryFactoryInterface
                            {
                                private static PdoDriverInterface $pdoDriverInterface;

                                public function __construct(PdoDriverInterface $pdoDriverInterface)
                                {
                                    static::$pdoDriverInterface = $pdoDriverInterface;
                                }

                                public function __invoke(): callable
                                {
                                    return new self(static::$pdoDriverInterface);
                                }

                                public static function createFromConfig(): PdoDriverInterface
                                {
                                    return self::$pdoDriverInterface;
                                }
                            };
                        }
                    },
                'PlatformInterfaceFactoryFactory'
                    => function () {
                        return new class () implements PlatformInterfaceFactoryFactoryInterface
                        {
                            public function __invoke(): callable
                            {
                                return new class () implements FactoryFactoryInterface
                                {
                                    public function __invoke(): callable
                                    {
                                        return new self();
                                    }

                                    public static function fromDriver(): Sql92
                                    {
                                        return new Sql92();
                                    }
                                };
                            }
                        };
                    },
            ],
        ];

        $this->serviceManager = new ServiceManager($config);

        $this->serviceManager->setService('config', [
            'db' => [
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
