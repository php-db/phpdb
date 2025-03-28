<?php

namespace LaminasTest\Db\TableGateway\Feature;

use Laminas\Db\Metadata\MetadataInterface;
use Laminas\Db\Metadata\Object\ConstraintObject;
use Laminas\Db\Metadata\Object\TableObject;
use Laminas\Db\Metadata\Object\ViewObject;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Db\TableGateway\Feature\MetadataFeature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class MetadataFeatureTest extends TestCase
{
    #[Group('integration-test')]
    public function testPostInitialize()
    {
        $tableGatewayMock = $this->getMockForAbstractClass(AbstractTableGateway::class);
        $metadataMock     = $this->getMockBuilder(MetadataInterface::class)->getMock();
        $metadataMock->expects($this->any())->method('getColumnNames')->willReturn(['id', 'name']);

        $constraintObject = new ConstraintObject('id_pk', 'table');
        $constraintObject->setColumns(['id']);
        $constraintObject->setType('PRIMARY KEY');

        $metadataMock->expects($this->any())->method('getConstraints')->willReturn([$constraintObject]);

        $feature = new MetadataFeature($metadataMock);
        $feature->setTableGateway($tableGatewayMock);
        $feature->postInitialize();

        self::assertEquals(['id', 'name'], $tableGatewayMock->getColumns());
    }

    public function testPostInitializeRecordsPrimaryKeyColumnToSharedMetadata()
    {
        /** @var AbstractTableGateway $tableGatewayMock */
        $tableGatewayMock = $this->getMockForAbstractClass(AbstractTableGateway::class);
        $metadataMock     = $this->getMockBuilder(MetadataInterface::class)->getMock();
        $metadataMock->expects($this->any())->method('getColumnNames')->willReturn(['id', 'name']);
        $metadataMock->expects($this->any())
            ->method('getTable')
            ->willReturn(new TableObject('foo'));

        $constraintObject = new ConstraintObject('id_pk', 'table');
        $constraintObject->setColumns(['id']);
        $constraintObject->setType('PRIMARY KEY');

        $metadataMock->expects($this->any())->method('getConstraints')->willReturn([$constraintObject]);

        $feature = new MetadataFeature($metadataMock);
        $feature->setTableGateway($tableGatewayMock);
        $feature->postInitialize();

        $r = new ReflectionProperty(MetadataFeature::class, 'sharedData');
        $r->setAccessible(true);
        $sharedData = $r->getValue($feature);

        self::assertTrue(
            isset($sharedData['metadata']['primaryKey']),
            'Shared data must have metadata entry for primary key'
        );
        self::assertSame($sharedData['metadata']['primaryKey'], 'id');
    }

    public function testPostInitializeRecordsListOfColumnsInPrimaryKeyToSharedMetadata()
    {
        /** @var AbstractTableGateway $tableGatewayMock */
        $tableGatewayMock = $this->getMockForAbstractClass(AbstractTableGateway::class);
        $metadataMock     = $this->getMockBuilder(MetadataInterface::class)->getMock();
        $metadataMock->expects($this->any())->method('getColumnNames')->willReturn(['id', 'name']);
        $metadataMock->expects($this->any())
            ->method('getTable')
            ->willReturn(new TableObject('foo'));

        $constraintObject = new ConstraintObject('id_pk', 'table');
        $constraintObject->setColumns(['composite', 'id']);
        $constraintObject->setType('PRIMARY KEY');

        $metadataMock->expects($this->any())->method('getConstraints')->willReturn([$constraintObject]);

        $feature = new MetadataFeature($metadataMock);
        $feature->setTableGateway($tableGatewayMock);
        $feature->postInitialize();

        $r = new ReflectionProperty(MetadataFeature::class, 'sharedData');
        $r->setAccessible(true);
        $sharedData = $r->getValue($feature);

        self::assertTrue(
            isset($sharedData['metadata']['primaryKey']),
            'Shared data must have metadata entry for primary key'
        );
        self::assertEquals(['composite', 'id'], $sharedData['metadata']['primaryKey']);
    }

    public function testPostInitializeSkipsPrimaryKeyCheckIfNotTable()
    {
        /** @var AbstractTableGateway $tableGatewayMock */
        $tableGatewayMock = $this->getMockForAbstractClass(AbstractTableGateway::class);
        $metadataMock     = $this->getMockBuilder(MetadataInterface::class)->getMock();
        $metadataMock->expects($this->any())->method('getColumnNames')->willReturn(['id', 'name']);
        $metadataMock->expects($this->any())
            ->method('getTable')
            ->willReturn(new ViewObject('foo'));

        $metadataMock->expects($this->never())->method('getConstraints');

        $feature = new MetadataFeature($metadataMock);
        $feature->setTableGateway($tableGatewayMock);
        $feature->postInitialize();
    }
}
