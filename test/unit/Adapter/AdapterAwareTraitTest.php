<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter;

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterAwareTrait;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDbTest\Adapter\TestAsset\ConcreteAdapterAwareObject;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[CoversMethod(AdapterAwareTrait::class, 'setDbAdapter')]
#[Group('unit')]
class AdapterAwareTraitTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function setDbAdapter(): void
    {
        $object = new ConcreteAdapterAwareObject();

        static::assertNull($object->getAdapter());

        $driver   = $this->createMock(DriverInterface::class);
        $platform = $this->createMock(PlatformInterface::class);

        $adapter = new Adapter($driver, $platform);

        $object->setDbAdapter($adapter);

        static::assertSame($adapter, $object->getAdapter());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function setDbAdapterSetsProperty(): void
    {
        $object = new ConcreteAdapterAwareObject();

        $driver   = $this->createMock(DriverInterface::class);
        $platform = $this->createMock(PlatformInterface::class);

        $adapter = new Adapter($driver, $platform);

        $object->setDbAdapter($adapter);

        $reflection = new ReflectionProperty($object, 'adapter');
        static::assertSame($adapter, $reflection->getValue($object));
    }
}
