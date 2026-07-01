<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver\Feature;

use PhpDb\Adapter\Driver\Feature\DriverFeatureInterface;
use PhpDb\Adapter\Driver\Feature\DriverFeatureProviderInterface;
use PhpDb\Adapter\Driver\Feature\DriverFeatureProviderTrait;
use PhpDb\Adapter\Exception\RuntimeException;
use PhpDbTest\Adapter\Driver\TestAsset\TestFeatureDriver;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[CoversMethod(DriverFeatureProviderTrait::class, 'addFeature')]
#[CoversMethod(DriverFeatureProviderTrait::class, 'addFeatures')]
#[CoversMethod(DriverFeatureProviderTrait::class, 'getFeature')]
final class DriverFeatureProviderTraitTest extends TestCase
{
    public function testAddFeatureSetsDriverAndStoresFeature(): void
    {
        $driver  = new TestFeatureDriver();
        $feature = $this->createMock(DriverFeatureInterface::class);
        $feature->expects(self::once())->method('setDriver')->with($driver);

        $driver->addFeature($feature);

        self::assertSame($feature, $driver->getFeature($feature::class));
    }

    public function testAddFeaturesAddsMultipleFeatures(): void
    {
        $driver   = new TestFeatureDriver();
        $feature1 = $this->createMock(DriverFeatureInterface::class);
        $feature2 = $this->createMock(DriverFeatureInterface::class);

        $driver->addFeatures([$feature1, $feature2]);

        self::assertNotFalse($driver->getFeature($feature1::class));
    }

    public function testGetFeatureReturnsFeatureByClassName(): void
    {
        $driver  = new TestFeatureDriver();
        $feature = $this->createMock(DriverFeatureInterface::class);

        $driver->addFeature($feature);

        self::assertSame($feature, $driver->getFeature($feature::class));
    }

    public function testGetFeatureReturnsFalseWhenNotFound(): void
    {
        $driver = new TestFeatureDriver();

        self::assertFalse($driver->getFeature('NonExistent'));
    }

    public function testAddFeatureThrowsWhenUsedOutsideDriverInterface(): void
    {
        $nonDriver = new class implements DriverFeatureProviderInterface {
            use DriverFeatureProviderTrait;
        };

        $feature = $this->createMock(DriverFeatureInterface::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('can only be composed into');

        $nonDriver->addFeature($feature);
    }
}
