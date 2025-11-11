<?php

namespace PhpDbTest\Adapter;

use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceManager;
use Override;
use PhpDb\Adapter\AdapterAbstractServiceFactory;
use PhpDb\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
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
        $this->serviceManager = new ServiceManager();

        $config = new Config([
            'abstract_factories' => [AdapterAbstractServiceFactory::class],
        ]);
        $config->configureServiceManager($this->serviceManager);

        $this->serviceManager->setService('config', [
            'db' => [
                'adapters' => [
                    'PhpDb\Adapter\Writer' => [
                        'driver' => 'mysqli',
                    ],
                    'PhpDb\Adapter\Reader' => [
                        'driver' => 'mysqli',
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
    #[RequiresPhpExtension('mysqli')]
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
