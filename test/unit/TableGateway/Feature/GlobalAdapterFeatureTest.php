<?php

declare(strict_types=1);

namespace PhpDbTest\TableGateway\Feature;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\TableGateway\AbstractTableGateway;
use PhpDb\TableGateway\Exception\RuntimeException;
use PhpDb\TableGateway\Feature\GlobalAdapterFeature;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class GlobalAdapterFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset the static adapters before each test
        $reflection = new ReflectionProperty(GlobalAdapterFeature::class, 'staticAdapters');
        $reflection->setValue(null, []);
    }

    protected function tearDown(): void
    {
        // Clean up static adapters after each test
        $reflection = new ReflectionProperty(GlobalAdapterFeature::class, 'staticAdapters');
        $reflection->setValue(null, []);
    }

    public function testSetStaticAdapter(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);

        GlobalAdapterFeature::setStaticAdapter($adapter);

        $result = GlobalAdapterFeature::getStaticAdapter();
        self::assertSame($adapter, $result);
    }

    public function testGetStaticAdapterThrowsExceptionWhenNoAdapterSet(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No database adapter was found in the static registry.');

        GlobalAdapterFeature::getStaticAdapter();
    }

    public function testPreInitializeSetsAdapterOnTableGateway(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);
        GlobalAdapterFeature::setStaticAdapter($adapter);

        /** @var AbstractTableGateway&MockObject $tableGatewayMock */
        $tableGatewayMock = $this->getMockBuilder(AbstractTableGateway::class)
            ->disableOriginalConstructor()
            ->getMock();

        $feature = new GlobalAdapterFeature();
        $feature->setTableGateway($tableGatewayMock);

        $feature->preInitialize();

        // Verify adapter was set on table gateway
        $reflection = new ReflectionProperty(AbstractTableGateway::class, 'adapter');
        $result = $reflection->getValue($tableGatewayMock);

        self::assertSame($adapter, $result);
    }

    public function testGetStaticAdapterReturnsDefaultAdapterWhenClassSpecificNotSet(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);

        // Set adapter on the base class
        GlobalAdapterFeature::setStaticAdapter($adapter);

        // Get adapter should return the default adapter
        $result = GlobalAdapterFeature::getStaticAdapter();

        self::assertSame($adapter, $result);
    }
}