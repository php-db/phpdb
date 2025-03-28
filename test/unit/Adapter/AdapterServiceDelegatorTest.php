<?php

namespace LaminasTest\Db\Adapter;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterAwareInterface;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\AdapterServiceDelegator;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\ServiceManager;
use LaminasTest\Db\Adapter\TestAsset\ConcreteAdapterAwareObject;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;

final class AdapterServiceDelegatorTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testSetAdapterShouldBeCalledForExistingAdapter(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects(self::once())
            ->method('has')
            ->with(AdapterInterface::class)
            ->willReturn(true);
        $container
            ->expects(self::once())
            ->method('get')
            ->with(AdapterInterface::class)
            ->willReturn($this->createMock(Adapter::class));

        $callback = static fn(): ConcreteAdapterAwareObject => new ConcreteAdapterAwareObject();

        /** @var ConcreteAdapterAwareObject $result */
        $result = (new AdapterServiceDelegator())(
            $container,
            ConcreteAdapterAwareObject::class,
            $callback
        );

        $this->assertInstanceOf(
            AdapterInterface::class,
            $result->getAdapter()
        );
    }

    /**
     * @throws Exception
     */
    public function testSetAdapterShouldBeCalledForOnlyConcreteAdapter(): void
    {
        $container = $this
            ->createMock(ContainerInterface::class);
        $container
            ->expects(self::once())
            ->method('has')
            ->with(AdapterInterface::class)
            ->willReturn(true);

        $container
            ->expects(self::once())
            ->method('get')
            ->with(AdapterInterface::class)
            ->willReturn($this->createMock(AdapterInterface::class));

        $callback = static fn(): ConcreteAdapterAwareObject => new ConcreteAdapterAwareObject();

        /** @var ConcreteAdapterAwareObject $result */
        $result = (new AdapterServiceDelegator())(
            $container,
            ConcreteAdapterAwareObject::class,
            $callback
        );

        $this->assertNull($result->getAdapter());
    }

    /**
     * @throws Exception
     */
    public function testSetAdapterShouldNotBeCalledForMissingAdapter(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects(self::once())
            ->method('has')
            ->with(AdapterInterface::class)
            ->willReturn(false);
        $container
            ->expects(self::never())
            ->method('get');

        $callback = static fn(): ConcreteAdapterAwareObject => new ConcreteAdapterAwareObject();

        /** @var ConcreteAdapterAwareObject $result */
        $result = (new AdapterServiceDelegator())(
            $container,
            ConcreteAdapterAwareObject::class,
            $callback
        );

        $this->assertNull($result->getAdapter());
    }

    /**
     * @throws Exception
     */
    public function testSetAdapterShouldNotBeCalledForWrongClassInstance(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects(self::never())
            ->method('has');

        $callback = static fn(): stdClass => new stdClass();

        $result = (new AdapterServiceDelegator())(
            $container,
            stdClass::class,
            $callback
        );

        $this->assertNotInstanceOf(AdapterAwareInterface::class, $result);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testDelegatorWithServiceManager(): void
    {
        $databaseAdapter = new Adapter($this->createMock(DriverInterface::class));

        $container = new ServiceManager([
            'invokables' => [
                ConcreteAdapterAwareObject::class => ConcreteAdapterAwareObject::class,
            ],
            'factories'  => [
                AdapterInterface::class => static fn() => $databaseAdapter,
            ],
            'delegators' => [
                ConcreteAdapterAwareObject::class => [
                    AdapterServiceDelegator::class,
                ],
            ],
        ]);

        /** @var ConcreteAdapterAwareObject $result */
        $result = $container->get(ConcreteAdapterAwareObject::class);

        $this->assertInstanceOf(
            AdapterInterface::class,
            $result->getAdapter()
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testDelegatorWithServiceManagerAndCustomAdapterName()
    {
        $databaseAdapter = new Adapter($this->createMock(DriverInterface::class));

        $container = new ServiceManager([
            'invokables' => [
                ConcreteAdapterAwareObject::class => ConcreteAdapterAwareObject::class,
            ],
            'factories'  => [
                'alternate-database-adapter' => static fn() => $databaseAdapter,
            ],
            'delegators' => [
                ConcreteAdapterAwareObject::class => [
                    new AdapterServiceDelegator('alternate-database-adapter'),
                ],
            ],
        ]);

        /** @var ConcreteAdapterAwareObject $result */
        $result = $container->get(ConcreteAdapterAwareObject::class);

        $this->assertInstanceOf(
            AdapterInterface::class,
            $result->getAdapter()
        );
    }

    /**
     * @throws Exception
     */
    public function testDelegatorWithPluginManager()
    {
        $databaseAdapter = new Adapter($this->createMock(DriverInterface::class));

        $container           = new ServiceManager([
            'factories' => [
                AdapterInterface::class => static fn() => $databaseAdapter,
            ],
        ]);
        $pluginManagerConfig = [
            'invokables' => [
                ConcreteAdapterAwareObject::class => ConcreteAdapterAwareObject::class,
            ],
            'delegators' => [
                ConcreteAdapterAwareObject::class => [
                    AdapterServiceDelegator::class,
                ],
            ],
        ];

        /** @var AbstractPluginManager $pluginManager */
        $pluginManager = new class ($container, $pluginManagerConfig) extends AbstractPluginManager {
        };

        $options = [
            'table' => 'foo',
            'field' => 'bar',
        ];

        /** @var ConcreteAdapterAwareObject $result */
        $result = $pluginManager->get(
            ConcreteAdapterAwareObject::class,
            $options
        );

        $this->assertInstanceOf(
            AdapterInterface::class,
            $result->getAdapter()
        );
        $this->assertSame($options, $result->getOptions());
    }
}
