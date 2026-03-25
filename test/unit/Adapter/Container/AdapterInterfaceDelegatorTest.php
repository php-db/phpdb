<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Container;

use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceManager;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Container\AdapterInterfaceDelegator;
use PhpDb\Exception\RuntimeException;
use PhpDb\ResultSet\ResultSetInterface;
use PhpDbTest\Adapter\TestAsset\ConcreteAdapterAwareObject;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;

#[Group('unit')]
#[CoversMethod(AdapterInterfaceDelegator::class, '__construct')]
#[CoversMethod(AdapterInterfaceDelegator::class, '__set_state')]
#[CoversMethod(AdapterInterfaceDelegator::class, '__invoke')]
final class AdapterInterfaceDelegatorTest extends TestCase
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
        $result = (new AdapterInterfaceDelegator())(
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
        $result = (new AdapterInterfaceDelegator())(
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

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('Service "PhpDb\Adapter\AdapterInterface" not found in container');

        (new AdapterInterfaceDelegator())(
            $container,
            ConcreteAdapterAwareObject::class,
            $callback
        );
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

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Delegated service "stdClass" must implement PhpDb\Adapter\AdapterAwareInterface'
        );

        (new AdapterInterfaceDelegator())(
            $container,
            stdClass::class,
            $callback
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testDelegatorWithServiceManager(): void
    {
        $databaseAdapter = new Adapter(
            $this->createMock(DriverInterface::class),
            $this->createMock(PlatformInterface::class),
            $this->createMock(ResultSetInterface::class)
        );

        $container = new ServiceManager([
            'invokables' => [
                ConcreteAdapterAwareObject::class => ConcreteAdapterAwareObject::class,
            ],
            'factories'  => [
                AdapterInterface::class => static fn() => $databaseAdapter,
            ],
            'delegators' => [
                ConcreteAdapterAwareObject::class => [
                    AdapterInterfaceDelegator::class,
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
        $databaseAdapter = new Adapter(
            $this->createMock(DriverInterface::class),
            $this->createMock(PlatformInterface::class),
            $this->createMock(ResultSetInterface::class)
        );

        $container = new ServiceManager([
            'invokables' => [
                ConcreteAdapterAwareObject::class => ConcreteAdapterAwareObject::class,
            ],
            'factories'  => [
                'alternate-database-adapter' => static fn() => $databaseAdapter,
            ],
            'delegators' => [
                ConcreteAdapterAwareObject::class => [
                    new AdapterInterfaceDelegator('alternate-database-adapter'),
                ],
            ],
        ]);

        $result = $container->get(ConcreteAdapterAwareObject::class);

        $this->assertInstanceOf(
            AdapterInterface::class,
            $result->getAdapter()
        );
    }

    public function testSetStateWithDefaultAdapterName(): void
    {
        $delegator = AdapterInterfaceDelegator::__set_state([]);

        self::assertInstanceOf(AdapterInterfaceDelegator::class, $delegator);
    }

    public function testSetStateWithCustomAdapterName(): void
    {
        $delegator = AdapterInterfaceDelegator::__set_state(['adapterName' => 'custom']);

        self::assertInstanceOf(AdapterInterfaceDelegator::class, $delegator);
    }

    public function testInvokeReturnsInstanceWhenAdapterIsNotAdapterInterface(): void
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
            ->willReturn(new stdClass());

        $callback = static fn(): ConcreteAdapterAwareObject => new ConcreteAdapterAwareObject();

        $result = (new AdapterInterfaceDelegator())(
            $container,
            ConcreteAdapterAwareObject::class,
            $callback
        );

        self::assertInstanceOf(ConcreteAdapterAwareObject::class, $result);
        self::assertNull($result->getAdapter());
    }
}
