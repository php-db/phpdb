<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter;

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterAwareTrait;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDbTest\Adapter\TestAsset\ConcreteAdapterAwareObject;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[CoversTrait(AdapterAwareTrait::class)]
#[Group('unit')]
class AdapterAwareTraitTest extends TestCase
{
    public function testSetDbAdapter(): void
    {
        $object = new ConcreteAdapterAwareObject();

        self::assertNull($object->getAdapter());

        $driver   = $this->createMock(DriverInterface::class);
        $platform = $this->createMock(PlatformInterface::class);

        $adapter = new Adapter($driver, $platform);

        $object->setDbAdapter($adapter);

        self::assertSame($adapter, $object->getAdapter());
    }

    public function testSetDbAdapterSetsProperty(): void
    {
        $object = new ConcreteAdapterAwareObject();

        $driver   = $this->createMock(DriverInterface::class);
        $platform = $this->createMock(PlatformInterface::class);

        $adapter = new Adapter($driver, $platform);

        $object->setDbAdapter($adapter);

        $reflection = new ReflectionProperty($object, 'adapter');
        self::assertSame($adapter, $reflection->getValue($object));
    }
}
