<?php

declare(strict_types=1);

namespace PhpDbTest\TableGateway\Feature;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Platform\Sql92;
use PhpDb\Metadata\MetadataInterface;
use PhpDb\Metadata\Object\ConstraintObject;
use PhpDb\TableGateway\AbstractTableGateway;
use PhpDb\TableGateway\Feature\FeatureSet;
use PhpDb\TableGateway\Feature\MetadataFeature;
use PhpDb\TableGateway\Feature\PrimaryReplicaFeature;
use PhpDb\TableGateway\Feature\SequenceFeature;
use PhpDbTest\TableGateway\Feature\TestAsset\TestTableGatewayFeature;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[IgnoreDeprecations]
#[RequiresPhp('<= 8.6')]
#[CoversMethod(FeatureSet::class, '__construct')]
#[CoversMethod(FeatureSet::class, 'setTableGateway')]
#[CoversMethod(FeatureSet::class, 'getFeatureByClassName')]
#[CoversMethod(FeatureSet::class, 'addFeatures')]
#[CoversMethod(FeatureSet::class, 'addFeature')]
#[CoversMethod(FeatureSet::class, 'apply')]
#[CoversMethod(FeatureSet::class, 'canCallMagicGet')]
#[CoversMethod(FeatureSet::class, 'callMagicGet')]
#[CoversMethod(FeatureSet::class, 'canCallMagicSet')]
#[CoversMethod(FeatureSet::class, 'callMagicSet')]
#[CoversMethod(FeatureSet::class, 'canCallMagicCall')]
#[CoversMethod(FeatureSet::class, 'callMagicCall')]
class FeatureSetTest extends TestCase
{
    /**
     * A feature added after the table gateway is known receives it on the way in.
     */
    #[Test]
    public function addFeaturePropagatesAKnownTableGateway(): void
    {
        $feature = new TestTableGatewayFeature();

        $tableGateway = $this->getMockBuilder(AbstractTableGateway::class)
            ->disableOriginalConstructor()
            ->getMock();

        $featureSet = new FeatureSet();
        $featureSet->setTableGateway($tableGateway);
        $featureSet->addFeature($feature);

        static::assertSame($tableGateway, $feature->receivedTableGateway());
    }

    #[Test]
    public function addFeaturesReturnsFluentInterface(): void
    {
        $feature1 = new SequenceFeature('id', 'seq1');
        $feature2 = new SequenceFeature('id', 'seq2');

        $featureSet = new FeatureSet();
        $result     = $featureSet->addFeatures([$feature1, $feature2]);

        static::assertSame($featureSet, $result);
    }

    /**
     * @cover FeatureSet::addFeature
     * @throws Exception
     */
    #[Test]
    #[Group('Laminas-4993')]
    public function addFeatureThatFeatureDoesNotHaveTableGatewayButFeatureSetHas(): void
    {
        $mockMasterAdapter = $this->getMockBuilder(AdapterInterface::class)->getMock();

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $mockDriver    = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('createStatement')->willReturn($mockStatement);
        $mockMasterAdapter->expects($this->any())->method('getDriver')->willReturn($mockDriver);
        $mockMasterAdapter->expects($this->any())->method('getPlatform')->willReturn(new Sql92());

        $mockReplicaAdapter = $this->getMockBuilder(AdapterInterface::class)->getMock();

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $mockDriver    = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('createStatement')->willReturn($mockStatement);
        $mockReplicaAdapter->expects($this->any())->method('getDriver')->willReturn($mockDriver);
        $mockReplicaAdapter->expects($this->any())->method('getPlatform')->willReturn(new Sql92());

        $tableGatewayMock = $this->getMockBuilder(AbstractTableGateway::class)->onlyMethods([])->getMock();

        $feature = new PrimaryReplicaFeature($mockReplicaAdapter);

        $featureSet = new FeatureSet();
        $featureSet->setTableGateway($tableGatewayMock);

        static::assertInstanceOf(FeatureSet::class, $featureSet->addFeature($feature));
    }

    /**
     * @cover FeatureSet::addFeature
     * @throws Exception
     */
    #[Test]
    #[Group('Laminas-4993')]
    public function addFeatureThatFeatureHasTableGatewayButFeatureSetDoesNotHave(): void
    {
        $tableGatewayMock = $this->getMockBuilder(AbstractTableGateway::class)->onlyMethods([])->getMock();

        $metadataMock = $this->getMockBuilder(MetadataInterface::class)->getMock();
        $metadataMock->expects($this->any())->method('getColumnNames')->willReturn(['id', 'name']);

        $constraintObject = new ConstraintObject('id_pk', 'table');
        $constraintObject->setColumns(['id']);
        $constraintObject->setType('PRIMARY KEY');

        $metadataMock->expects($this->any())->method('getConstraints')->willReturn([$constraintObject]);

        $feature = new MetadataFeature($metadataMock);
        $feature->setTableGateway($tableGatewayMock);

        $featureSet = new FeatureSet();
        static::assertInstanceOf(FeatureSet::class, $featureSet->addFeature($feature));
    }

    #[Test]
    public function applyCallsAllFeaturesWhenNoHalt(): void
    {
        $feature1 = new TestTableGatewayFeature();
        $feature2 = new TestTableGatewayFeature();

        $featureSet = new FeatureSet([$feature1, $feature2]);
        $featureSet->apply('recordCall', []);

        static::assertTrue($feature1->called);
        static::assertTrue($feature2->called);
    }

    #[Test]
    public function applyCallsMethodOnFeatures(): void
    {
        $tableGatewayMock = $this->getMockBuilder(AbstractTableGateway::class)
            ->disableOriginalConstructor()
            ->getMock();

        $feature = new PrimaryReplicaFeature(
            $this->getMockBuilder(AdapterInterface::class)->getMock(),
        );

        $featureSet = new FeatureSet([$feature]);
        $featureSet->setTableGateway($tableGatewayMock);

        $featureSet->apply('preSelect', []);

        /** @phpstan-ignore staticMethod.alreadyNarrowedType */
        static::assertTrue(true);
    }

    /**
     * A feature that does not implement the method is skipped, not treated as the end
     * of the list.
     */
    #[Test]
    public function applyContinuesPastFeaturesLackingTheMethod(): void
    {
        $feature = new TestTableGatewayFeature();

        $featureSet = new FeatureSet([new SequenceFeature('id', 'seq'), $feature]);
        $featureSet->apply('recordCall', []);

        static::assertTrue($feature->called);
    }

    #[Test]
    public function applyHaltsWhenFeatureReturnsHalt(): void
    {
        $feature1              = new TestTableGatewayFeature();
        $feature1->returnValue = FeatureSet::APPLY_HALT;

        $feature2 = new TestTableGatewayFeature();

        $featureSet = new FeatureSet([$feature1, $feature2]);
        $featureSet->apply('recordCall', []);

        static::assertTrue($feature1->called);
        static::assertFalse($feature2->called);
    }

    #[Test]
    public function applyPassesArgumentsToFeatures(): void
    {
        $feature = new TestTableGatewayFeature();

        $featureSet = new FeatureSet([$feature]);
        $featureSet->apply('recordCall', ['test value']);

        static::assertEquals(['test value'], $feature->receivedArgs);
    }

    #[Test]
    public function applySkipsFeatureWithoutMethod(): void
    {
        $feature    = new SequenceFeature('id', 'table_sequence');
        $featureSet = new FeatureSet([$feature]);

        $featureSet->apply('nonExistentMethod', []);

        /** @phpstan-ignore staticMethod.alreadyNarrowedType */
        static::assertTrue(true);
    }

    #[Test]
    public function callMagicCallReturnsNullWhenNoFeatureHasMethod(): void
    {
        $featureSet = new FeatureSet();

        static::assertNull($featureSet->callMagicCall('nonExistentMethod', []));
    }

    #[Test]
    public function callMagicCallSucceedsForValidMethodOfAddedFeature(): void
    {
        $feature = new TestTableGatewayFeature();

        $featureSet = new FeatureSet();
        $featureSet->addFeature($feature);

        $result = $featureSet->callMagicCall('customMethod', ['test_value']);

        static::assertSame('result: test_value', $result);
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
    public function canCallMagicCallReturnsFalseForAddedMethodOfAddedFeature(): void
    {
        $feature    = new SequenceFeature('id', 'table_sequence');
        $featureSet = new FeatureSet();
        $featureSet->addFeature($feature);

        static::assertFalse(
            $featureSet->canCallMagicCall('postInitialize'),
            'Should have been able to call postInitialize from the MetaData Feature',
        );
    }

    #[Test]
    public function canCallMagicCallReturnsFalseWhenNoFeaturesHaveBeenAdded(): void
    {
        $featureSet = new FeatureSet();
        static::assertFalse(
            $featureSet->canCallMagicCall('lastSequenceId'),
        );
    }

    #[Test]
    public function canCallMagicCallReturnsTrueForAddedMethodOfAddedFeature(): void
    {
        $feature    = new SequenceFeature('id', 'table_sequence');
        $featureSet = new FeatureSet();
        $featureSet->addFeature($feature);

        static::assertTrue(
            $featureSet->canCallMagicCall('lastSequenceId'),
            'Should have been able to call lastSequenceId from the Sequence Feature',
        );
    }

    #[Test]
    public function canCallMagicGetReturnsFalse(): void
    {
        $featureSet = new FeatureSet();

        static::assertFalse($featureSet->canCallMagicGet('property'));
    }

    #[Test]
    public function canCallMagicSetReturnsFalse(): void
    {
        $featureSet = new FeatureSet();

        static::assertFalse($featureSet->canCallMagicSet('property'));
    }

    #[Test]
    public function constructorWithFeatures(): void
    {
        $feature    = new SequenceFeature('id', 'table_sequence');
        $featureSet = new FeatureSet([$feature]);

        static::assertSame($feature, $featureSet->getFeatureByClassName(SequenceFeature::class));
    }

    #[Test]
    public function getFeatureByClassNameReturnsNullWhenNotFound(): void
    {
        $featureSet = new FeatureSet();

        $result = $featureSet->getFeatureByClassName(SequenceFeature::class);

        static::assertNull($result);
    }

    /**
     * The first feature of the requested class wins; the scan stops rather than
     * running on to a later one.
     */
    #[Test]
    public function getFeatureByClassNameReturnsTheFirstMatch(): void
    {
        $first  = new TestTableGatewayFeature();
        $second = new TestTableGatewayFeature();

        $featureSet = new FeatureSet([$first, $second]);

        static::assertSame($first, $featureSet->getFeatureByClassName(TestTableGatewayFeature::class));
    }

    #[Test]
    public function getFeatureByClassNameSkipsFeaturesOfAnotherClass(): void
    {
        $other  = new TestTableGatewayFeature();
        $wanted = new SequenceFeature('id', 'table_sequence');

        $featureSet = new FeatureSet([$other, $wanted]);

        static::assertSame($wanted, $featureSet->getFeatureByClassName(SequenceFeature::class));
    }

    #[Test]
    public function setTableGateway(): void
    {
        $tableGatewayMock = $this->getMockBuilder(AbstractTableGateway::class)
            ->disableOriginalConstructor()
            ->getMock();

        $feature    = new SequenceFeature('id', 'table_sequence');
        $featureSet = new FeatureSet([$feature]);

        $result = $featureSet->setTableGateway($tableGatewayMock);

        static::assertSame($featureSet, $result);
    }

    /**
     * Features added before the table gateway is known must still receive it.
     */
    #[Test]
    public function setTableGatewayPropagatesToFeaturesAddedEarlier(): void
    {
        $feature = new TestTableGatewayFeature();

        $tableGateway = $this->getMockBuilder(AbstractTableGateway::class)
            ->disableOriginalConstructor()
            ->getMock();

        (new FeatureSet([$feature]))->setTableGateway($tableGateway);

        static::assertSame($tableGateway, $feature->receivedTableGateway());
    }
}
