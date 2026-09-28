<?php

declare(strict_types=1);

namespace PhpDbTest\TableGateway\Feature;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\ResultSet\ResultSetInterface;
use PhpDb\ResultSet\RowPrototypeResultSet;
use PhpDb\RowGateway\RowGatewayInterface;
use PhpDb\TableGateway\AbstractTableGateway;
use PhpDb\TableGateway\Exception\RuntimeException;
use PhpDb\TableGateway\Feature\FeatureSet;
use PhpDb\TableGateway\Feature\MetadataFeature;
use PhpDb\TableGateway\Feature\RowGatewayFeature;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class RowGatewayFeatureTest extends TestCase
{
    #[Test]
    public function constructorStoresArguments(): void
    {
        $feature = new RowGatewayFeature('id');

        // Use reflection to check the constructorArguments property
        $property = new ReflectionProperty(RowGatewayFeature::class, 'constructorArguments');
        $args     = $property->getValue($feature);

        static::assertEquals(['id'], $args);
    }

    #[Test]
    public function constructorStoresRowGatewayInstance(): void
    {
        /** @var RowGatewayInterface&MockObject $rowGateway */
        $rowGateway = $this->createMock(RowGatewayInterface::class);

        $feature = new RowGatewayFeature($rowGateway);

        // Use reflection to check the constructorArguments property
        $property = new ReflectionProperty(RowGatewayFeature::class, 'constructorArguments');
        $args     = $property->getValue($feature);

        static::assertSame($rowGateway, $args[0]);
    }

    #[Test]
    public function constructorWithNoArguments(): void
    {
        $feature = new RowGatewayFeature();

        // Use reflection to check the constructorArguments property
        $property = new ReflectionProperty(RowGatewayFeature::class, 'constructorArguments');
        $args     = $property->getValue($feature);

        static::assertEquals([], $args);
    }

    #[Test]
    public function postInitializeIgnoresAnArgumentThatIsNeitherStringNorRowGateway(): void
    {
        $resultSet    = $this->createInitialResultSet();
        $tableGateway = $this->createTableGatewayMock($resultSet);
        $original     = $resultSet->getRowPrototype();

        $feature = new RowGatewayFeature(42);
        $feature->setTableGateway($tableGateway);

        $feature->postInitialize();

        static::assertSame($original, $resultSet->getRowPrototype());
    }

    #[Test]
    public function postInitializeThrowsExceptionForNonResultSet(): void
    {
        $resultSet    = $this->createMock(ResultSetInterface::class);
        $tableGateway = $this->createTableGatewayMock($resultSet);

        $feature = new RowGatewayFeature('id');
        $feature->setTableGateway($tableGateway);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('expects the ResultSet to be an instance of');

        $feature->postInitialize();
    }

    #[Test]
    public function postInitializeThrowsExceptionWhenMetadataHasNoMetadataKey(): void
    {
        $resultSet = $this->createInitialResultSet();

        // Create a MetadataFeature mock without the metadata key in sharedData
        $metadataFeature = $this->getMockBuilder(MetadataFeature::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set empty sharedData on the metadata feature
        $sharedDataProperty = new ReflectionProperty(MetadataFeature::class, 'sharedData');
        $sharedDataProperty->setValue($metadataFeature, []);

        $featureSet = $this->createMock(FeatureSet::class);
        $featureSet->expects($this->once())
            ->method('getFeatureByClassName')
            ->with(MetadataFeature::class)
            ->willReturn($metadataFeature);

        $tableGateway = $this->createTableGatewayMock($resultSet, $featureSet);

        $feature = new RowGatewayFeature();
        $feature->setTableGateway($tableGateway);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No information was provided to the RowGatewayFeature');

        $feature->postInitialize();
    }

    #[Test]
    public function postInitializeThrowsExceptionWhenNoMetadataAndNoPrimaryKey(): void
    {
        $resultSet = $this->createInitialResultSet();

        $featureSet = $this->createMock(FeatureSet::class);
        $featureSet->expects($this->once())
            ->method('getFeatureByClassName')
            ->with(MetadataFeature::class)
            ->willReturn(null);

        $tableGateway = $this->createTableGatewayMock($resultSet, $featureSet);

        $feature = new RowGatewayFeature();
        $feature->setTableGateway($tableGateway);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No information was provided to the RowGatewayFeature');

        $feature->postInitialize();
    }

    #[Test]
    public function postInitializeThrowsWhenMetadataIsNotAnArray(): void
    {
        $resultSet = $this->createInitialResultSet();

        $metadataFeature = $this->getMockBuilder(MetadataFeature::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sharedDataProperty = new ReflectionProperty(MetadataFeature::class, 'sharedData');
        $sharedDataProperty->setValue($metadataFeature, ['metadata' => 'not-an-array']);

        $featureSet = $this->createMock(FeatureSet::class);
        $featureSet->expects($this->once())
            ->method('getFeatureByClassName')
            ->with(MetadataFeature::class)
            ->willReturn($metadataFeature);

        $feature = new RowGatewayFeature();
        $feature->setTableGateway($this->createTableGatewayMock($resultSet, $featureSet));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The MetadataFeature did not expose its metadata as an array.');

        $feature->postInitialize();
    }

    #[Test]
    public function postInitializeThrowsWhenMetadataPrimaryKeyIsUnusable(): void
    {
        $resultSet = $this->createInitialResultSet();

        $metadataFeature = $this->getMockBuilder(MetadataFeature::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sharedDataProperty = new ReflectionProperty(MetadataFeature::class, 'sharedData');
        $sharedDataProperty->setValue($metadataFeature, [
            'metadata' => ['primaryKey' => 42],
        ]);

        $featureSet = $this->createMock(FeatureSet::class);
        $featureSet->expects($this->once())
            ->method('getFeatureByClassName')
            ->with(MetadataFeature::class)
            ->willReturn($metadataFeature);

        $feature = new RowGatewayFeature();
        $feature->setTableGateway($this->createTableGatewayMock($resultSet, $featureSet));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'The MetadataFeature did not expose a usable primary key for RowGateway object creation.',
        );

        $feature->postInitialize();
    }

    #[Test]
    public function postInitializeThrowsWhenTableGatewayHasNoFeatureSet(): void
    {
        $resultSet    = $this->createInitialResultSet();
        $tableGateway = $this->createTableGatewayMock($resultSet);

        $featureSetProperty = new ReflectionProperty(AbstractTableGateway::class, 'featureSet');
        $featureSetProperty->setValue($tableGateway, null);

        $feature = new RowGatewayFeature();
        $feature->setTableGateway($tableGateway);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No information was provided to the RowGatewayFeature');

        $feature->postInitialize();
    }

    #[Test]
    public function postInitializeThrowsWhenTableIsNotNamed(): void
    {
        $resultSet    = $this->createInitialResultSet();
        $tableGateway = $this->createTableGatewayMock($resultSet);

        $tableProperty = new ReflectionProperty(AbstractTableGateway::class, 'table');
        $tableProperty->setValue($tableGateway, [42]);

        $feature = new RowGatewayFeature('id');
        $feature->setTableGateway($tableGateway);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'The table gateway must reference a named table before a RowGateway prototype can be created.',
        );

        $feature->postInitialize();
    }

    #[Test]
    public function postInitializeWithMetadataFeature(): void
    {
        $resultSet = $this->createInitialResultSet();

        // Create a MetadataFeature mock with primary key in sharedData
        $metadataFeature = $this->getMockBuilder(MetadataFeature::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set sharedData with metadata containing primaryKey
        $sharedDataProperty = new ReflectionProperty(MetadataFeature::class, 'sharedData');
        $sharedDataProperty->setValue($metadataFeature, [
            'metadata' => ['primaryKey' => 'id'],
        ]);

        $featureSet = $this->createMock(FeatureSet::class);
        $featureSet->expects($this->once())
            ->method('getFeatureByClassName')
            ->with(MetadataFeature::class)
            ->willReturn($metadataFeature);

        $tableGateway = $this->createTableGatewayMock($resultSet, $featureSet);

        $feature = new RowGatewayFeature();
        $feature->setTableGateway($tableGateway);

        $feature->postInitialize();

        $prototype = $resultSet->getRowPrototype();
        static::assertInstanceOf(RowGatewayInterface::class, $prototype);
    }

    #[Test]
    public function postInitializeWithRowGatewayInstance(): void
    {
        $resultSet = $this->createInitialResultSet();

        /** @var RowGatewayInterface&MockObject $rowGateway */
        $rowGateway = $this->createMock(RowGatewayInterface::class);

        $tableGateway = $this->createTableGatewayMock($resultSet);

        $feature = new RowGatewayFeature($rowGateway);
        $feature->setTableGateway($tableGateway);

        $feature->postInitialize();

        static::assertSame($rowGateway, $resultSet->getRowPrototype());
    }

    #[Test]
    public function postInitializeWithStringPrimaryKey(): void
    {
        $resultSet    = $this->createInitialResultSet();
        $tableGateway = $this->createTableGatewayMock($resultSet);

        $feature = new RowGatewayFeature('id');
        $feature->setTableGateway($tableGateway);

        $feature->postInitialize();

        $prototype = $resultSet->getRowPrototype();
        static::assertInstanceOf(RowGatewayInterface::class, $prototype);
    }

    /**
     * RowPrototypeResultSet requires a prototype up front; postInitialize() always replaces it.
     */
    private function createInitialResultSet(): RowPrototypeResultSet
    {
        return new RowPrototypeResultSet($this->createMock(RowGatewayInterface::class));
    }

    private function createTableGatewayMock(
        ResultSetInterface $resultSetPrototype,
        ?FeatureSet $featureSet = null,
    ): AbstractTableGateway&MockObject {
        /** @var AbstractTableGateway&MockObject $tableGateway */
        $tableGateway = $this->getMockBuilder(AbstractTableGateway::class)
            ->disableOriginalConstructor()
            ->getMock();

        $adapter = $this->createMock(AdapterInterface::class);

        $tableProperty = new ReflectionProperty(AbstractTableGateway::class, 'table');
        $tableProperty->setValue($tableGateway, 'test_table');

        $adapterProperty = new ReflectionProperty(AbstractTableGateway::class, 'adapter');
        $adapterProperty->setValue($tableGateway, $adapter);

        $resultSetProperty = new ReflectionProperty(AbstractTableGateway::class, 'resultSetPrototype');
        $resultSetProperty->setValue($tableGateway, $resultSetPrototype);

        if (null !== $featureSet) {
            $featureSetProperty = new ReflectionProperty(AbstractTableGateway::class, 'featureSet');
            $featureSetProperty->setValue($tableGateway, $featureSet);
        }

        return $tableGateway;
    }
}
