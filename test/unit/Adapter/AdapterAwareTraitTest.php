<?php

namespace LaminasTest\Db\Adapter;

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterAwareTrait;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use LaminasTest\Db\DeprecatedAssertionsTrait;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionException;

class AdapterAwareTraitTest extends TestCase
{
    use DeprecatedAssertionsTrait;

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function testSetDbAdapter(): void
    {
        $object = $this->getObjectForTrait(AdapterAwareTrait::class);

        self::assertAttributeEquals(null, 'adapter', $object);

        $driver   = $this->getMockBuilder(DriverInterface::class)->getMock();
        $platform = $this->getMockBuilder(PlatformInterface::class)->getMock();

        $adapter = new Adapter($driver, $platform);

        $object->setDbAdapter($adapter);

        self::assertAttributeEquals($adapter, 'adapter', $object);
    }
}
