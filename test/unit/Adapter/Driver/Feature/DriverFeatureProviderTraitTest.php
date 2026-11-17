<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver\Feature;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\Feature\DriverFeatureInterface;
use PhpDb\Adapter\Driver\Feature\DriverFeatureProviderInterface;
use PhpDb\Adapter\Driver\Feature\DriverFeatureProviderTrait;
use PhpDb\Adapter\Exception\RuntimeException;
use PhpDbTest\Adapter\Driver\TestAsset\TestFeatureDriver;
use PhpDbTest\Adapter\Driver\TestAsset\TestNonProviderFeatureDriver;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[Group('unit')]
#[CoversMethod(DriverFeatureProviderTrait::class, 'addFeature')]
#[CoversMethod(DriverFeatureProviderTrait::class, 'addFeatures')]
#[CoversMethod(DriverFeatureProviderTrait::class, 'getFeature')]
#[CoversMethod(DriverFeatureProviderTrait::class, 'asFeatureProvidingDriver')]
final class DriverFeatureProviderTraitTest extends TestCase
{
    #[Test]
    public function addFeatureReturnsTheDriver(): void
    {
        $driver = new TestFeatureDriver();

        static::assertSame($driver, $driver->addFeature($this->createMock(DriverFeatureInterface::class)));
    }

    #[Test]
    public function addFeaturesAddsMultipleFeatures(): void
    {
        $driver   = new TestFeatureDriver();
        $feature1 = $this->createMock(DriverFeatureInterface::class);
        $feature2 = $this->createMock(DriverFeatureInterface::class);

        $driver->addFeatures([$feature1, $feature2]);

        static::assertNotFalse($driver->getFeature($feature1::class));
    }

    #[Test]
    public function addFeatureSetsDriverAndStoresFeature(): void
    {
        $driver  = new TestFeatureDriver();
        $feature = $this->createMock(DriverFeatureInterface::class);
        $feature->expects(self::once())->method('setDriver')->with($driver);

        $driver->addFeature($feature);

        static::assertSame($feature, $driver->getFeature($feature::class));
    }

    #[Test]
    public function addFeaturesReturnsTheDriver(): void
    {
        $driver = new TestFeatureDriver();

        static::assertSame($driver, $driver->addFeatures([$this->createMock(DriverFeatureInterface::class)]));
    }

    #[Test]
    public function addFeaturesThrowsWhenUsedOutsideDriverInterfaceEvenWithNoFeatures(): void
    {
        $nonDriver = new class implements DriverFeatureProviderInterface {
            use DriverFeatureProviderTrait;
        };

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(sprintf(
            RuntimeException::UNCOMPOSABLE_TRAIT,
            DriverFeatureProviderTrait::class,
            DriverInterface::class,
        ));

        $nonDriver->addFeatures([]);
    }

    #[Test]
    public function addFeatureThrowsWhenTheDriverIsNotAFeatureProvider(): void
    {
        $driver  = new TestNonProviderFeatureDriver();
        $feature = $this->createMock(DriverFeatureInterface::class);
        $feature->expects(self::never())->method('setDriver');

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(sprintf(
            RuntimeException::UNCOMPOSABLE_TRAIT,
            DriverFeatureProviderTrait::class,
            DriverFeatureProviderInterface::class,
        ));

        $driver->addFeature($feature);
    }

    #[Test]
    public function addFeatureThrowsWhenUsedOutsideDriverInterface(): void
    {
        $nonDriver = new class implements DriverFeatureProviderInterface {
            use DriverFeatureProviderTrait;
        };

        $feature = $this->createMock(DriverFeatureInterface::class);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(sprintf(
            RuntimeException::UNCOMPOSABLE_TRAIT,
            DriverFeatureProviderTrait::class,
            DriverInterface::class,
        ));

        $nonDriver->addFeature($feature);
    }

    #[Test]
    public function getFeatureReturnsFalseWhenNotFound(): void
    {
        $driver = new TestFeatureDriver();

        static::assertFalse($driver->getFeature('NonExistent'));
    }

    #[Test]
    public function getFeatureReturnsFeatureByClassName(): void
    {
        $driver  = new TestFeatureDriver();
        $feature = $this->createMock(DriverFeatureInterface::class);

        $driver->addFeature($feature);

        static::assertSame($feature, $driver->getFeature($feature::class));
    }
}
