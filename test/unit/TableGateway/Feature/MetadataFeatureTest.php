<?php

declare(strict_types=1);

namespace PhpDbTest\TableGateway\Feature;

use PhpDb\Metadata\MetadataInterface;
use PhpDb\Metadata\Object\ConstraintObject;
use PhpDb\Metadata\Object\TableObject;
use PhpDb\Metadata\Object\ViewObject;
use PhpDb\Sql\TableIdentifier;
use PhpDb\TableGateway\AbstractTableGateway;
use PhpDb\TableGateway\Exception\RuntimeException;
use PhpDb\TableGateway\Feature\MetadataFeature;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[IgnoreDeprecations]
#[RequiresPhp('<= 8.6')]
class MetadataFeatureTest extends TestCase
{
    #[Test]
    public function constructorSetsInitialSharedData(): void
    {
        $metadataMock = $this->getMockBuilder(MetadataInterface::class)->getMock();
        $feature      = new MetadataFeature($metadataMock);

        $r          = new ReflectionProperty(MetadataFeature::class, 'sharedData');
        $sharedData = $r->getValue($feature);

        static::assertIsArray($sharedData);
        static::assertArrayHasKey('metadata', $sharedData);
        static::assertNull($sharedData['metadata']['primaryKey']);
        static::assertEquals([], $sharedData['metadata']['columns']);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Test]
    public function postInitializeRecordsListOfColumnsInPrimaryKeyToSharedMetadata(): void
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
            ->willReturn(new TableObject('foo'));

        $constraintObject = new ConstraintObject('id_pk', 'table');
        $constraintObject->setColumns(['composite', 'id']);
        $constraintObject->setType('PRIMARY KEY');

        $metadataMock->expects($this->any())->method('getConstraints')->willReturn([$constraintObject]);

        $feature = new MetadataFeature($metadataMock);
        $feature->setTableGateway($tableGatewayMock);
        $feature->postInitialize();

        $r          = new ReflectionProperty(MetadataFeature::class, 'sharedData');
        $sharedData = $r->getValue($feature);

        static::assertIsArray($sharedData);
        static::assertArrayHasKey('metadata', $sharedData);
        static::assertIsArray($sharedData['metadata']);
        static::assertArrayHasKey('primaryKey', $sharedData['metadata']);
        static::assertEquals(['composite', 'id'], $sharedData['metadata']['primaryKey']);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Test]
    public function postInitializeRecordsPrimaryKeyColumnToSharedMetadata(): void
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
            ->willReturn(new TableObject('foo'));

        $constraintObject = new ConstraintObject('id_pk', 'table');
        $constraintObject->setColumns(['id']);
        $constraintObject->setType('PRIMARY KEY');

        $metadataMock->expects($this->any())->method('getConstraints')->willReturn([$constraintObject]);

        $feature = new MetadataFeature($metadataMock);
        $feature->setTableGateway($tableGatewayMock);
        $feature->postInitialize();

        $r          = new ReflectionProperty(MetadataFeature::class, 'sharedData');
        $sharedData = $r->getValue($feature);

        static::assertIsArray($sharedData);
        static::assertArrayHasKey('metadata', $sharedData);
        static::assertIsArray($sharedData['metadata']);
        static::assertArrayHasKey('primaryKey', $sharedData['metadata']);
        static::assertSame('id', $sharedData['metadata']['primaryKey']);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Test]
    public function postInitializeSkipsConstraintsThatAreNotPrimaryKeys(): void
    {
        /** @var AbstractTableGateway&MockObject $tableGatewayMock */
        $tableGatewayMock = $this->getMockBuilder(AbstractTableGateway::class)->onlyMethods([])->getMock();

        $tableProperty = new ReflectionProperty(AbstractTableGateway::class, 'table');
        $tableProperty->setValue($tableGatewayMock, 'foo');

        $metadataMock = $this->getMockBuilder(MetadataInterface::class)->getMock();
        $metadataMock->expects($this->any())->method('getColumnNames')->willReturn(['id', 'name']);
        $metadataMock->expects($this->any())
            ->method('getTable')
            ->willReturn(new TableObject('foo'));

        $unique = new ConstraintObject('name_unique', 'foo');
        $unique->setColumns(['name']);
        $unique->setType('UNIQUE');

        $primary = new ConstraintObject('id_pk', 'foo');
        $primary->setColumns(['id']);
        $primary->setType('PRIMARY KEY');

        $metadataMock->expects($this->any())->method('getConstraints')->willReturn([$unique, $primary]);

        $feature = new MetadataFeature($metadataMock);
        $feature->setTableGateway($tableGatewayMock);
        $feature->postInitialize();

        $r          = new ReflectionProperty(MetadataFeature::class, 'sharedData');
        $sharedData = $r->getValue($feature);

        static::assertIsArray($sharedData);
        static::assertArrayHasKey('metadata', $sharedData);
        static::assertIsArray($sharedData['metadata']);
        static::assertArrayHasKey('primaryKey', $sharedData['metadata']);
        static::assertSame('id', $sharedData['metadata']['primaryKey']);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Test]
    public function postInitializeSkipsPrimaryKeyCheckIfNotTable(): void
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

    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Test]
    public function postInitializeThrowsExceptionWhenNoPrimaryKeyFound(): void
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
            ->willReturn(new TableObject('foo'));

        // Return empty constraints - no PRIMARY KEY
        $metadataMock->expects($this->any())->method('getConstraints')->willReturn([]);

        $feature = new MetadataFeature($metadataMock);
        $feature->setTableGateway($tableGatewayMock);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A primary key for this column could not be found in the metadata.');

        $feature->postInitialize();
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function postInitializeThrowsWhenTableIsNotNamed(): void
    {
        $metadataMock     = $this->getMockBuilder(MetadataInterface::class)->getMock();
        $tableGatewayMock = $this->getMockBuilder(AbstractTableGateway::class)->onlyMethods([])->getMock();

        $tableProperty = new ReflectionProperty(AbstractTableGateway::class, 'table');
        $tableProperty->setValue($tableGatewayMock, [42]);

        $feature = new MetadataFeature($metadataMock);
        $feature->setTableGateway($tableGatewayMock);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'The table gateway must reference a named table before metadata can be resolved.',
        );

        $feature->postInitialize();
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Test]
    public function postInitializeWithArrayTable(): void
    {
        /** @var AbstractTableGateway&MockObject $tableGatewayMock */
        $tableGatewayMock = $this->getMockBuilder(AbstractTableGateway::class)->onlyMethods([])->getMock();

        // Set the table property as an array (aliased table)
        $tableProperty = new ReflectionProperty(AbstractTableGateway::class, 'table');
        $tableProperty->setValue($tableGatewayMock, ['t' => 'foo']);

        $metadataMock = $this->getMockBuilder(MetadataInterface::class)->getMock();
        $metadataMock->expects($this->any())
            ->method('getColumnNames')
            ->with('foo', null)
            ->willReturn(['id', 'name']);
        $metadataMock->expects($this->any())
            ->method('getTable')
            ->willReturn(new TableObject('foo'));

        $constraintObject = new ConstraintObject('id_pk', 'foo');
        $constraintObject->setColumns(['id']);
        $constraintObject->setType('PRIMARY KEY');

        $metadataMock->expects($this->any())->method('getConstraints')->willReturn([$constraintObject]);

        $feature = new MetadataFeature($metadataMock);
        $feature->setTableGateway($tableGatewayMock);
        $feature->postInitialize();

        $r          = new ReflectionProperty(MetadataFeature::class, 'sharedData');
        $sharedData = $r->getValue($feature);

        static::assertSame('id', $sharedData['metadata']['primaryKey']);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Test]
    public function postInitializeWithTableIdentifier(): void
    {
        /** @var AbstractTableGateway&MockObject $tableGatewayMock */
        $tableGatewayMock = $this->getMockBuilder(AbstractTableGateway::class)->onlyMethods([])->getMock();

        // Set the table property as a TableIdentifier
        $tableIdentifier = new TableIdentifier('foo', 'myschema');
        $tableProperty   = new ReflectionProperty(AbstractTableGateway::class, 'table');
        $tableProperty->setValue($tableGatewayMock, $tableIdentifier);

        $metadataMock = $this->getMockBuilder(MetadataInterface::class)->getMock();
        $metadataMock->expects($this->any())
            ->method('getColumnNames')
            ->with('foo', 'myschema')
            ->willReturn(['id', 'name']);
        $metadataMock->expects($this->any())
            ->method('getTable')
            ->with('foo', 'myschema')
            ->willReturn(new TableObject('foo'));

        $constraintObject = new ConstraintObject('id_pk', 'foo');
        $constraintObject->setColumns(['id']);
        $constraintObject->setType('PRIMARY KEY');

        $metadataMock->expects($this->any())
            ->method('getConstraints')
            ->with('foo', 'myschema')
            ->willReturn([$constraintObject]);

        $feature = new MetadataFeature($metadataMock);
        $feature->setTableGateway($tableGatewayMock);
        $feature->postInitialize();

        $r          = new ReflectionProperty(MetadataFeature::class, 'sharedData');
        $sharedData = $r->getValue($feature);

        static::assertSame('id', $sharedData['metadata']['primaryKey']);
    }
}
