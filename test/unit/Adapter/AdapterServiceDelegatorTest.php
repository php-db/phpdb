<?php

namespace PhpDbTest\Adapter;

use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\ServiceManager;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterAwareInterface;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\AdapterServiceDelegator;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDbTest\Adapter\TestAsset\ConcreteAdapterAwareObject;
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
    public function testDelegatorWithServiceManagerAndCustomAdapterName(): void
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

        $result = $container->get(ConcreteAdapterAwareObject::class);

        $this->assertInstanceOf(
            AdapterInterface::class,
            $result->getAdapter()
        );
    }

    /**
     * @throws Exception
     */
    public function testDelegatorWithPluginManager(): void
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
            public function validate(mixed $instance): void
            {
            }
        };

        $options = [
            'table' => 'foo',
            'field' => 'bar',
        ];

        /** @var ConcreteAdapterAwareObject $result */
        $result = $pluginManager->get(
            ConcreteAdapterAwareObject::class
        );

        $this->assertInstanceOf(
            AdapterInterface::class,
            $result->getAdapter()
        );
        $this->assertSame($options, $result->getOptions());
    }
}
