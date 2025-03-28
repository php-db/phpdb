<?php

namespace LaminasTest\Db\TableGateway\Feature;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Driver\Pgsql\Result;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Adapter\Platform\Postgresql;
use Laminas\Db\Adapter\Platform\Sql92;
use Laminas\Db\Metadata\MetadataInterface;
use Laminas\Db\Metadata\Object\ConstraintObject;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Db\TableGateway\Feature\FeatureSet;
use Laminas\Db\TableGateway\Feature\MasterSlaveFeature;
use Laminas\Db\TableGateway\Feature\MetadataFeature;
use Laminas\Db\TableGateway\Feature\SequenceFeature;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversMethod(FeatureSet::class, 'canCallMagicCall')]
#[CoversMethod(FeatureSet::class, 'callMagicCall')]
class FeatureSetTest extends TestCase
{
    /**
     * @cover FeatureSet::addFeature
     * @throws Exception
     */
    #[Group('Laminas-4993')]
    public function testAddFeatureThatFeatureDoesNotHaveTableGatewayButFeatureSetHas()
    {
        $mockMasterAdapter = $this->getMockBuilder(AdapterInterface::class)->getMock();

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $mockDriver    = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('createStatement')->willReturn($mockStatement);
        $mockMasterAdapter->expects($this->any())->method('getDriver')->willReturn($mockDriver);
        $mockMasterAdapter->expects($this->any())->method('getPlatform')->willReturn(new Sql92());

        $mockSlaveAdapter = $this->getMockBuilder(AdapterInterface::class)->getMock();

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $mockDriver    = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('createStatement')->willReturn($mockStatement);
        $mockSlaveAdapter->expects($this->any())->method('getDriver')->willReturn($mockDriver);
        $mockSlaveAdapter->expects($this->any())->method('getPlatform')->willReturn(new Sql92());

        $tableGatewayMock = $this->getMockForAbstractClass(AbstractTableGateway::class);

        //feature doesn't have tableGateway, but FeatureSet has
        $feature = new MasterSlaveFeature($mockSlaveAdapter);

        $featureSet = new FeatureSet();
        $featureSet->setTableGateway($tableGatewayMock);

        self::assertInstanceOf(FeatureSet::class, $featureSet->addFeature($feature));
    }

    /**
     * @cover FeatureSet::addFeature
     * @throws Exception
     */
    #[Group('Laminas-4993')]
    public function testAddFeatureThatFeatureHasTableGatewayButFeatureSetDoesNotHave()
    {
        $tableGatewayMock = $this->getMockForAbstractClass(AbstractTableGateway::class);

        $metadataMock = $this->getMockBuilder(MetadataInterface::class)->getMock();
        $metadataMock->expects($this->any())->method('getColumnNames')->willReturn(['id', 'name']);

        $constraintObject = new ConstraintObject('id_pk', 'table');
        $constraintObject->setColumns(['id']);
        $constraintObject->setType('PRIMARY KEY');

        $metadataMock->expects($this->any())->method('getConstraints')->willReturn([$constraintObject]);

        //feature have tableGateway, but FeatureSet doesn't has
        $feature = new MetadataFeature($metadataMock);
        $feature->setTableGateway($tableGatewayMock);

        $featureSet = new FeatureSet();
        self::assertInstanceOf(FeatureSet::class, $featureSet->addFeature($feature));
    }

    public function testCanCallMagicCallReturnsTrueForAddedMethodOfAddedFeature()
    {
        $feature    = new SequenceFeature('id', 'table_sequence');
        $featureSet = new FeatureSet();
        $featureSet->addFeature($feature);

        self::assertTrue(
            $featureSet->canCallMagicCall('lastSequenceId'),
            "Should have been able to call lastSequenceId from the Sequence Feature"
        );
    }

    public function testCanCallMagicCallReturnsFalseForAddedMethodOfAddedFeature()
    {
        $feature    = new SequenceFeature('id', 'table_sequence');
        $featureSet = new FeatureSet();
        $featureSet->addFeature($feature);

        self::assertFalse(
            $featureSet->canCallMagicCall('postInitialize'),
            "Should have been able to call postInitialize from the MetaData Feature"
        );
    }

    public function testCanCallMagicCallReturnsFalseWhenNoFeaturesHaveBeenAdded()
    {
        $featureSet = new FeatureSet();
        self::assertFalse(
            $featureSet->canCallMagicCall('lastSequenceId')
        );
    }

    public function testCallMagicCallSucceedsForValidMethodOfAddedFeature()
    {
        $sequenceName = 'table_sequence';

        $platformMock = $this->getMockBuilder(Postgresql::class)->getMock();
        $platformMock->expects($this->any())
            ->method('getName')->willReturn('PostgreSQL');

        $resultMock = $this->getMockBuilder(Result::class)->getMock();
        $resultMock->expects($this->any())
            ->method('current')
            ->willReturn(['currval' => 1]);

        $statementMock = $this->getMockBuilder(StatementInterface::class)->getMock();
        $statementMock->expects($this->any())
            ->method('prepare')
            ->with('SELECT CURRVAL(\'' . $sequenceName . '\')');
        $statementMock->expects($this->any())
            ->method('execute')
            ->willReturn($resultMock);

        $adapterMock = $this->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $adapterMock->expects($this->any())
            ->method('getPlatform')->willReturn($platformMock);
        $adapterMock->expects($this->any())
            ->method('createStatement')->willReturn($statementMock);

        $tableGatewayMock = $this->getMockBuilder(AbstractTableGateway::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectionClass    = new ReflectionClass(AbstractTableGateway::class);
        $reflectionProperty = $reflectionClass->getProperty('adapter');
        /** @psalm-suppress UnusedMethodCall */
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($tableGatewayMock, $adapterMock);

        $feature = new SequenceFeature('id', 'table_sequence');
        $feature->setTableGateway($tableGatewayMock);
        $featureSet = new FeatureSet();
        $featureSet->addFeature($feature);
        self::assertEquals(1, $featureSet->callMagicCall('lastSequenceId', null));
    }
}
