<?php

namespace LaminasTest\Db\Adapter;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterAbstractServiceFactory;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class AdapterAbstractServiceFactoryTest extends TestCase
{
    /** @var ServiceManager|ContainerInterface */
    private ServiceManager|ContainerInterface $serviceManager;

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
                    'Laminas\Db\Adapter\Writer' => [
                        'driver' => 'mysqli',
                    ],
                    'Laminas\Db\Adapter\Reader' => [
                        'driver' => 'mysqli',
                    ],
                ],
            ],
        ]);
    }

    public static function providerValidService(): array
    {
        return [
            ['Laminas\Db\Adapter\Writer'],
            ['Laminas\Db\Adapter\Reader'],
        ];
    }

    public static function providerInvalidService(): array
    {
        return [
            ['Laminas\Db\Adapter\Unknown'],
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
        self::assertInstanceOf(Adapter::class, $actual);
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
