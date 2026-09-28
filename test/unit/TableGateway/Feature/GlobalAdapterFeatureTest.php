<?php

declare(strict_types=1);

namespace PhpDbTest\TableGateway\Feature;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\TableGateway\AbstractTableGateway;
use PhpDb\TableGateway\Exception\RuntimeException;
use PhpDb\TableGateway\Feature\GlobalAdapterFeature;
use PhpDbTest\TableGateway\Feature\TestAsset\TestGlobalAdapterFeatureSubclass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class GlobalAdapterFeatureTest extends TestCase
{
    #[Test]
    public function getStaticAdapterReturnsDefaultAdapterWhenClassSpecificNotSet(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);

        // Set adapter on the base class
        GlobalAdapterFeature::setStaticAdapter($adapter);

        // Get adapter should return the default adapter
        $result = GlobalAdapterFeature::getStaticAdapter();

        static::assertSame($adapter, $result);
    }

    #[Test]
    public function getStaticAdapterThrowsExceptionWhenNoAdapterSet(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No database adapter was found in the static registry.');

        GlobalAdapterFeature::getStaticAdapter();
    }

    #[Test]
    public function preInitializeSetsAdapterOnTableGateway(): void
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
        $result     = $reflection->getValue($tableGatewayMock);

        static::assertSame($adapter, $result);
    }

    #[Test]
    public function setStaticAdapter(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);

        GlobalAdapterFeature::setStaticAdapter($adapter);

        $result = GlobalAdapterFeature::getStaticAdapter();
        static::assertSame($adapter, $result);
    }

    #[Test]
    public function subclassCanSetAndGetOwnAdapter(): void
    {
        $baseAdapter     = $this->createMock(AdapterInterface::class);
        $subclassAdapter = $this->createMock(AdapterInterface::class);

        // Set default adapter on base class
        GlobalAdapterFeature::setStaticAdapter($baseAdapter);

        // Set a different adapter on the subclass
        TestGlobalAdapterFeatureSubclass::setStaticAdapter($subclassAdapter);

        // Base class should return base adapter
        static::assertSame($baseAdapter, GlobalAdapterFeature::getStaticAdapter());

        // Subclass should return its own adapter
        static::assertSame($subclassAdapter, TestGlobalAdapterFeatureSubclass::getStaticAdapter());
    }

    #[Test]
    public function subclassFallsBackToDefaultAdapterWhenNoSpecificAdapterSet(): void
    {
        $defaultAdapter = $this->createMock(AdapterInterface::class);

        // Only set adapter on base class
        GlobalAdapterFeature::setStaticAdapter($defaultAdapter);

        // Subclass should fall back to default adapter
        $result = TestGlobalAdapterFeatureSubclass::getStaticAdapter();

        static::assertSame($defaultAdapter, $result);
    }

    #[Test]
    public function subclassThrowsExceptionWhenNoAdaptersSet(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No database adapter was found in the static registry.');

        TestGlobalAdapterFeatureSubclass::getStaticAdapter();
    }

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
}
