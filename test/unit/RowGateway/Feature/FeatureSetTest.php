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
        $feature    = $this->createMock(AbstractFeature::class);
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
        // setRowGateway is called once when setRowGateway is called on the FeatureSet
        // (features added before setRowGateway is called don't have setRowGateway called on them until later)
        $feature->expects($this->once())
            ->method('setRowGateway')
            ->with($rowGateway);

        $featureSet = new FeatureSet([$feature]);
        $result     = $featureSet->setRowGateway($rowGateway);

        self::assertSame($featureSet, $result);
    }

    public function testGetFeatureByClassNameReturnsFeature(): void
    {
        $feature    = $this->createMock(AbstractFeature::class);
        $featureSet = new FeatureSet([$feature]);

        $result = $featureSet->getFeatureByClassName(AbstractFeature::class);

        self::assertSame($feature, $result);
    }

    public function testGetFeatureByClassNameReturnsNullWhenNotFound(): void
    {
        $featureSet = new FeatureSet();

        $result = $featureSet->getFeatureByClassName(AbstractFeature::class);

        self::assertNull($result);
    }

    public function testAddFeatures(): void
    {
        $feature1 = $this->createMock(AbstractFeature::class);
        $feature2 = $this->createMock(AbstractFeature::class);

        $featureSet = new FeatureSet();
        $result     = $featureSet->addFeatures([$feature1, $feature2]);

        self::assertSame($featureSet, $result);
        self::assertSame($feature1, $featureSet->getFeatureByClassName(AbstractFeature::class));
    }

    public function testAddFeature(): void
    {
        $feature = $this->createMock(AbstractFeature::class);

        $featureSet = new FeatureSet();
        $result     = $featureSet->addFeature($feature);

        self::assertSame($featureSet, $result);
        self::assertSame($feature, $featureSet->getFeatureByClassName(AbstractFeature::class));
    }

    public function testAddFeatureCallsSetRowGatewayWhenRowGatewayIsSet(): void
    {
        /** @var AbstractRowGateway&MockObject $rowGateway */
        $rowGateway = $this->getMockBuilder(AbstractRowGateway::class)
            ->disableOriginalConstructor()
            ->getMock();

        $feature = $this->createMock(AbstractFeature::class);
        $feature->expects($this->once())
            ->method('setRowGateway')
            ->with($rowGateway);

        // Create FeatureSet and set rowGateway FIRST
        $featureSet = new FeatureSet();
        $featureSet->setRowGateway($rowGateway);

        // Now add feature - should call setRowGateway on the feature
        $featureSet->addFeature($feature);
    }

    public function testApplyCallsMethodOnFeatures(): void
    {
        $called       = false;
        $receivedArgs = [];

        $feature = new class ($called, $receivedArgs) extends AbstractFeature {
            /** @var bool @phpstan-ignore property.onlyWritten */
            private $called;
            /** @var array<mixed> @phpstan-ignore property.onlyWritten */
            private $receivedArgs;

            public function __construct(bool &$called, array &$receivedArgs)
            {
                $this->called       = &$called;
                $this->receivedArgs = &$receivedArgs;
            }

            public function preInitialize(string $arg1, string $arg2): void
            {
                $this->called       = true;
                $this->receivedArgs = [$arg1, $arg2];
            }
        };

        $featureSet = new FeatureSet([$feature]);
        $featureSet->apply('preInitialize', ['arg1', 'arg2']);

        self::assertTrue($called);
        self::assertEquals(['arg1', 'arg2'], $receivedArgs);
    }

    public function testApplyHaltsWhenFeatureReturnsHalt(): void
    {
        $feature1Called = false;
        $feature2Called = false;

        $feature1 = new class ($feature1Called) extends AbstractFeature {
            /** @var bool @phpstan-ignore property.onlyWritten */
            private $called;

            public function __construct(bool &$called)
            {
                $this->called = &$called;
            }

            public function preInitialize(): string
            {
                $this->called = true;
                return FeatureSet::APPLY_HALT;
            }
        };

        $feature2 = new class ($feature2Called) extends AbstractFeature {
            /** @var bool @phpstan-ignore property.onlyWritten */
            private $called;

            public function __construct(bool &$called)
            {
                $this->called = &$called;
            }

            public function preInitialize(): void
            {
                $this->called = true;
            }
        };

        $featureSet = new FeatureSet([$feature1, $feature2]);
        $featureSet->apply('preInitialize', []);

        self::assertTrue($feature1Called);
        self::assertFalse($feature2Called);
    }

    public function testApplySkipsFeatureWithoutMethod(): void
    {
        $feature = $this->createMock(AbstractFeature::class);

        $featureSet = new FeatureSet([$feature]);
        // Should not throw - just skips
        $featureSet->apply('nonExistentMethod', []);

        /** @phpstan-ignore staticMethod.alreadyNarrowedType */
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
