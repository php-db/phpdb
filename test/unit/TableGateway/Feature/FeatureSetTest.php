<?php

declare(strict_types=1);

namespace PhpDbTest\TableGateway\Feature;

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\Pgsql\Result;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Platform\Postgresql;
use PhpDb\Adapter\Platform\Sql92;
use PhpDb\Metadata\MetadataInterface;
use PhpDb\Metadata\Object\ConstraintObject;
use PhpDb\TableGateway\AbstractTableGateway;
use PhpDb\TableGateway\Feature\FeatureSet;
use PhpDb\TableGateway\Feature\MasterSlaveFeature;
use PhpDb\TableGateway\Feature\MetadataFeature;
use PhpDb\TableGateway\Feature\SequenceFeature;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[RequiresPhp('<= 8.4')]
#[CoversMethod(FeatureSet::class, 'canCallMagicCall')]
#[CoversMethod(FeatureSet::class, 'callMagicCall')]
class FeatureSetTest extends TestCase
{
    /**
     * @cover FeatureSet::addFeature
     * @throws Exception
     */
    #[Group('Laminas-4993')]
    public function testAddFeatureThatFeatureDoesNotHaveTableGatewayButFeatureSetHas(): void
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

        $tableGatewayMock = $this->getMockBuilder(AbstractTableGateway::class)->onlyMethods([])->getMock();

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
    public function testAddFeatureThatFeatureHasTableGatewayButFeatureSetDoesNotHave(): void
    {
        $tableGatewayMock = $this->getMockBuilder(AbstractTableGateway::class)->onlyMethods([])->getMock();

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

    public function testCanCallMagicCallReturnsTrueForAddedMethodOfAddedFeature(): void
    {
        $feature    = new SequenceFeature('id', 'table_sequence');
        $featureSet = new FeatureSet();
        $featureSet->addFeature($feature);

        self::assertTrue(
            $featureSet->canCallMagicCall('lastSequenceId'),
            'Should have been able to call lastSequenceId from the Sequence Feature'
        );
    }

    public function testCanCallMagicCallReturnsFalseForAddedMethodOfAddedFeature(): void
    {
        $feature    = new SequenceFeature('id', 'table_sequence');
        $featureSet = new FeatureSet();
        $featureSet->addFeature($feature);

        self::assertFalse(
            $featureSet->canCallMagicCall('postInitialize'),
            'Should have been able to call postInitialize from the MetaData Feature'
        );
    }

    public function testCanCallMagicCallReturnsFalseWhenNoFeaturesHaveBeenAdded(): void
    {
        $featureSet = new FeatureSet();
        self::assertFalse(
            $featureSet->canCallMagicCall('lastSequenceId')
        );
    }

    public function testCallMagicCallSucceedsForValidMethodOfAddedFeature(): void
    {
        $this->markTestSkipped('This needs refactored to use a custom TestFeature and Sql92');
        /** @phpstan-ignore deadCode.unreachable */
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
        /** @noinspection PhpExpressionResultUnusedInspection */
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($tableGatewayMock, $adapterMock);

        $feature = new SequenceFeature('id', 'table_sequence');
        $feature->setTableGateway($tableGatewayMock);
        $featureSet = new FeatureSet();
        $featureSet->addFeature($feature);
        self::assertEquals(1, $featureSet->callMagicCall('lastSequenceId', []));
    }
}
