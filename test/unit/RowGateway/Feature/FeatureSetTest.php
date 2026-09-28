<?php

declare(strict_types=1);

namespace PhpDbTest\RowGateway\Feature;

use PhpDb\RowGateway\AbstractRowGateway;
use PhpDb\RowGateway\Feature\AbstractFeature;
use PhpDb\RowGateway\Feature\FeatureInterface;
use PhpDb\RowGateway\Feature\FeatureSet;
use PhpDbTest\RowGateway\Feature\TestAsset\TestRowGatewayFeature;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FeatureSetTest extends TestCase
{
    #[Test]
    public function addFeature(): void
    {
        $feature = $this->createMock(AbstractFeature::class);

        $featureSet = new FeatureSet();
        $result     = $featureSet->addFeature($feature);

        static::assertSame($featureSet, $result);
        static::assertSame($feature, $featureSet->getFeatureByClassName(AbstractFeature::class));
    }

    #[Test]
    public function addFeatureCallsSetRowGatewayWhenRowGatewayIsSet(): void
    {
        /** @var AbstractRowGateway&MockObject $rowGateway */
        $rowGateway = $this->getMockBuilder(AbstractRowGateway::class)
            ->disableOriginalConstructor()
            ->getMock();

        $feature = $this->createMock(AbstractFeature::class);
        $feature->expects($this->once())
            ->method('setRowGateway')
            ->with($rowGateway);

        $featureSet = new FeatureSet();
        $featureSet->setRowGateway($rowGateway);
        $featureSet->addFeature($feature);
    }

    #[Test]
    public function addFeatures(): void
    {
        $feature1 = $this->createMock(AbstractFeature::class);
        $feature2 = $this->createMock(AbstractFeature::class);

        $featureSet = new FeatureSet();
        $result     = $featureSet->addFeatures([$feature1, $feature2]);

        static::assertSame($featureSet, $result);
        static::assertSame($feature1, $featureSet->getFeatureByClassName(AbstractFeature::class));
    }

    #[Test]
    public function applyCallsMethodOnFeatures(): void
    {
        $feature = new TestRowGatewayFeature();

        $featureSet = new FeatureSet([$feature]);
        $featureSet->apply('preInitialize', ['arg1', 'arg2']);

        static::assertTrue($feature->called);
        static::assertEquals(['arg1', 'arg2'], $feature->receivedArgs);
    }

    #[Test]
    public function applyHaltsWhenFeatureReturnsHalt(): void
    {
        $feature1              = new TestRowGatewayFeature();
        $feature1->returnValue = FeatureSet::APPLY_HALT;

        $feature2 = new TestRowGatewayFeature();

        $featureSet = new FeatureSet([$feature1, $feature2]);
        $featureSet->apply('preInitialize', []);

        static::assertTrue($feature1->called);
        static::assertFalse($feature2->called);
    }

    #[Test]
    public function applySkipsFeatureWithoutMethod(): void
    {
        $feature = $this->createMock(AbstractFeature::class);

        $featureSet = new FeatureSet([$feature]);
        $featureSet->apply('nonExistentMethod', []);

        /** @phpstan-ignore staticMethod.alreadyNarrowedType */
        static::assertTrue(true);
    }

    #[Test]
    public function callMagicCallReturnsNull(): void
    {
        $featureSet = new FeatureSet();
        static::assertNull($featureSet->callMagicCall('method', []));
    }

    #[Test]
    public function callMagicGetReturnsNull(): void
    {
        $featureSet = new FeatureSet();
        static::assertNull($featureSet->callMagicGet('property'));
    }

    #[Test]
    public function callMagicSetReturnsNull(): void
    {
        $featureSet = new FeatureSet();
        static::assertNull($featureSet->callMagicSet('property', 'value'));
    }

    #[Test]
    public function canCallMagicCallReturnsFalse(): void
    {
        $featureSet = new FeatureSet();
        static::assertFalse($featureSet->canCallMagicCall('method'));
    }

    #[Test]
    public function canCallMagicGetReturnsFalse(): void
    {
        $featureSet = new FeatureSet();
        /** @phpstan-ignore staticMethod.impossibleType */
        static::assertFalse($featureSet->canCallMagicGet('property'));
    }

    #[Test]
    public function canCallMagicSetReturnsFalse(): void
    {
        $featureSet = new FeatureSet();
        /** @phpstan-ignore staticMethod.impossibleType */
        static::assertFalse($featureSet->canCallMagicSet('property'));
    }

    #[Test]
    public function constructorWithEmptyArray(): void
    {
        $featureSet = new FeatureSet();
        static::assertInstanceOf(FeatureSet::class, $featureSet);
    }

    #[Test]
    public function constructorWithFeatures(): void
    {
        $feature    = $this->createMock(AbstractFeature::class);
        $featureSet = new FeatureSet([$feature]);
        static::assertInstanceOf(FeatureSet::class, $featureSet);
    }

    #[Test]
    public function getFeatureByClassNameReturnsFeature(): void
    {
        $feature    = $this->createMock(AbstractFeature::class);
        $featureSet = new FeatureSet([$feature]);

        $result = $featureSet->getFeatureByClassName(AbstractFeature::class);

        static::assertSame($feature, $result);
    }

    #[Test]
    public function getFeatureByClassNameReturnsNullWhenNotFound(): void
    {
        $featureSet = new FeatureSet();

        $result = $featureSet->getFeatureByClassName(AbstractFeature::class);

        static::assertNull($result);
    }

    #[Test]
    public function getFeatureByClassNameSkipsFeaturesOfAnotherClass(): void
    {
        $other  = $this->createMock(FeatureInterface::class);
        $wanted = $this->createMock(AbstractFeature::class);

        $featureSet = new FeatureSet([$other, $wanted]);

        static::assertSame($wanted, $featureSet->getFeatureByClassName(AbstractFeature::class));
    }

    #[Test]
    public function setRowGateway(): void
    {
        /** @var AbstractRowGateway&MockObject $rowGateway */
        $rowGateway = $this->getMockBuilder(AbstractRowGateway::class)
            ->disableOriginalConstructor()
            ->getMock();

        $feature = $this->createMock(AbstractFeature::class);
        $feature->expects($this->once())
            ->method('setRowGateway')
            ->with($rowGateway);

        $featureSet = new FeatureSet([$feature]);
        $result     = $featureSet->setRowGateway($rowGateway);

        static::assertSame($featureSet, $result);
    }
}
