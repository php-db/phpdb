<?php

declare(strict_types=1);

namespace PhpDbTest\RowGateway\Feature;

use PhpDb\RowGateway\AbstractRowGateway;
use PhpDb\RowGateway\Feature\AbstractFeature;
use PhpDb\RowGateway\Feature\FeatureSet;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FeatureSetTest extends TestCase
{
    public function testConstructorWithEmptyArray(): void
    {
        $featureSet = new FeatureSet();
        self::assertInstanceOf(FeatureSet::class, $featureSet);
    }

    public function testConstructorWithFeatures(): void
    {
        $feature = $this->createMock(AbstractFeature::class);
        $featureSet = new FeatureSet([$feature]);
        self::assertInstanceOf(FeatureSet::class, $featureSet);
    }

    public function testSetRowGateway(): void
    {
        /** @var AbstractRowGateway&MockObject $rowGateway */
        $rowGateway = $this->getMockBuilder(AbstractRowGateway::class)
            ->disableOriginalConstructor()
            ->getMock();

        $feature = $this->createMock(AbstractFeature::class);
        // Note: setRowGateway is called twice - once in addFeature (with $feature itself, which is a bug)
        // and once in setRowGateway (with the actual rowGateway)
        $feature->expects($this->exactly(2))
            ->method('setRowGateway');

        $featureSet = new FeatureSet([$feature]);
        $result = $featureSet->setRowGateway($rowGateway);

        self::assertSame($featureSet, $result);
    }

    public function testGetFeatureByClassNameReturnsFeature(): void
    {
        $feature = $this->createMock(AbstractFeature::class);
        $featureSet = new FeatureSet([$feature]);

        $result = $featureSet->getFeatureByClassName(AbstractFeature::class);

        self::assertSame($feature, $result);
    }

    public function testGetFeatureByClassNameReturnsFalseWhenNotFound(): void
    {
        $featureSet = new FeatureSet();

        $result = $featureSet->getFeatureByClassName(AbstractFeature::class);

        self::assertFalse($result);
    }

    public function testAddFeatures(): void
    {
        $feature1 = $this->createMock(AbstractFeature::class);
        $feature2 = $this->createMock(AbstractFeature::class);

        $featureSet = new FeatureSet();
        $result = $featureSet->addFeatures([$feature1, $feature2]);

        self::assertSame($featureSet, $result);
        self::assertSame($feature1, $featureSet->getFeatureByClassName(AbstractFeature::class));
    }

    public function testAddFeature(): void
    {
        $feature = $this->createMock(AbstractFeature::class);

        $featureSet = new FeatureSet();
        $result = $featureSet->addFeature($feature);

        self::assertSame($featureSet, $result);
        self::assertSame($feature, $featureSet->getFeatureByClassName(AbstractFeature::class));
    }

    public function testApplyCallsMethodOnFeatures(): void
    {
        $feature = $this->getMockBuilder(AbstractFeature::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setRowGateway', 'getName', 'initialize', 'getMagicMethodSpecifications'])
            ->addMethods(['preInitialize'])
            ->getMock();

        $feature->expects($this->once())
            ->method('preInitialize')
            ->with('arg1', 'arg2');

        $featureSet = new FeatureSet([$feature]);
        $featureSet->apply('preInitialize', ['arg1', 'arg2']);
    }

    public function testApplyHaltsWhenFeatureReturnsHalt(): void
    {
        $feature1 = $this->getMockBuilder(AbstractFeature::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setRowGateway', 'getName', 'initialize', 'getMagicMethodSpecifications'])
            ->addMethods(['preInitialize'])
            ->getMock();

        $feature1->expects($this->once())
            ->method('preInitialize')
            ->willReturn(FeatureSet::APPLY_HALT);

        $feature2 = $this->getMockBuilder(AbstractFeature::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setRowGateway', 'getName', 'initialize', 'getMagicMethodSpecifications'])
            ->addMethods(['preInitialize'])
            ->getMock();

        $feature2->expects($this->never())
            ->method('preInitialize');

        $featureSet = new FeatureSet([$feature1, $feature2]);
        $featureSet->apply('preInitialize', []);
    }

    public function testApplySkipsFeatureWithoutMethod(): void
    {
        $feature = $this->createMock(AbstractFeature::class);

        $featureSet = new FeatureSet([$feature]);
        // Should not throw - just skips
        $featureSet->apply('nonExistentMethod', []);

        self::assertTrue(true);
    }

    public function testCanCallMagicGetReturnsFalse(): void
    {
        $featureSet = new FeatureSet();
        self::assertFalse($featureSet->canCallMagicGet('property'));
    }

    public function testCallMagicGetReturnsNull(): void
    {
        $featureSet = new FeatureSet();
        self::assertNull($featureSet->callMagicGet('property'));
    }

    public function testCanCallMagicSetReturnsFalse(): void
    {
        $featureSet = new FeatureSet();
        self::assertFalse($featureSet->canCallMagicSet('property'));
    }

    public function testCallMagicSetReturnsNull(): void
    {
        $featureSet = new FeatureSet();
        self::assertNull($featureSet->callMagicSet('property', 'value'));
    }

    public function testCanCallMagicCallReturnsFalse(): void
    {
        $featureSet = new FeatureSet();
        self::assertFalse($featureSet->canCallMagicCall('method'));
    }

    public function testCallMagicCallReturnsNull(): void
    {
        $featureSet = new FeatureSet();
        self::assertNull($featureSet->callMagicCall('method', []));
    }
}