<?php

declare(strict_types=1);

namespace PhpDbTest\TableGateway\Feature;

use PhpDb\Metadata\MetadataInterface;
use PhpDb\Metadata\Object\ConstraintObject;
use PhpDb\Metadata\Object\TableObject;
use PhpDb\Metadata\Object\ViewObject;
use PhpDb\TableGateway\AbstractTableGateway;
use PhpDb\TableGateway\Feature\MetadataFeature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[IgnoreDeprecations]
#[RequiresPhp('<= 8.6')]
class MetadataFeatureTest extends TestCase
{
    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Group('integration-test')]
    public function testPostInitialize(): void
    {
        $this->markTestSkipped('This is an integration test and requires a database connection.');
        /** @phpstan-ignore deadCode.unreachable */
        $tableGatewayMock = $this->getMockBuilder(AbstractTableGateway::class)
        ->onlyMethods([])
        ->getMock();
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

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testPostInitializeRecordsPrimaryKeyColumnToSharedMetadata(): void
    {
        $this->markTestSkipped('This should be an integration test');

        /** @var AbstractTableGateway&MockObject $tableGatewayMock */
        /** @phpstan-ignore deadCode.unreachable */
        $tableGatewayMock = $this->getMockBuilder(AbstractTableGateway::class)->onlyMethods([])->getMock();
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
        /** @noinspection PhpExpressionResultUnusedInspection */
        $r->setAccessible(true);
        $sharedData = $r->getValue($feature);

        self::assertIsArray($sharedData);
        self::assertTrue(
            isset($sharedData['metadata']['primaryKey']),
            'Shared data must have metadata entry for primary key'
        );
        self::assertSame('id', $sharedData['metadata']['primaryKey']);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testPostInitializeRecordsListOfColumnsInPrimaryKeyToSharedMetadata(): void
    {
        $this->markTestSkipped('This should be an integration test');

        /** @var AbstractTableGateway&MockObject $tableGatewayMock */
        /** @phpstan-ignore deadCode.unreachable */
        $tableGatewayMock = $this->getMockBuilder(AbstractTableGateway::class)->onlyMethods([])->getMock();
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
        /** @noinspection PhpExpressionResultUnusedInspection */
        $r->setAccessible(true);
        $sharedData = $r->getValue($feature);

        self::assertIsArray($sharedData);
        self::assertTrue(
            isset($sharedData['metadata']['primaryKey']),
            'Shared data must have metadata entry for primary key'
        );
        self::assertEquals(['composite', 'id'], $sharedData['metadata']['primaryKey']);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testPostInitializeSkipsPrimaryKeyCheckIfNotTable(): void
    {
        /** @var AbstractTableGateway&MockObject $tableGatewayMock */
        $tableGatewayMock = $this->getMockBuilder(AbstractTableGateway::class)->onlyMethods([])->getMock();

        // Set the table property on the mock using reflection
        $tableProperty = new ReflectionProperty(AbstractTableGateway::class, 'table');
        $tableProperty->setValue($tableGatewayMock, 'foo');

        $metadataMock = $this->getMockBuilder(MetadataInterface::class)->getMock();
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
