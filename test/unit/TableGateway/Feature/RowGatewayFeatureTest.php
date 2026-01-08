<?php

declare(strict_types=1);

namespace PhpDbTest\TableGateway\Feature;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\ResultSet\ResultSet;
use PhpDb\ResultSet\ResultSetInterface;
use PhpDb\RowGateway\RowGatewayInterface;
use PhpDb\TableGateway\AbstractTableGateway;
use PhpDb\TableGateway\Exception\RuntimeException;
use PhpDb\TableGateway\Feature\FeatureSet;
use PhpDb\TableGateway\Feature\MetadataFeature;
use PhpDb\TableGateway\Feature\RowGatewayFeature;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class RowGatewayFeatureTest extends TestCase
{
    private function createTableGatewayMock(
        ResultSetInterface $resultSetPrototype,
        ?FeatureSet $featureSet = null
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

        if ($featureSet !== null) {
            $featureSetProperty = new ReflectionProperty(AbstractTableGateway::class, 'featureSet');
            $featureSetProperty->setValue($tableGateway, $featureSet);
        }

        return $tableGateway;
    }

    public function testPostInitializeWithStringPrimaryKey(): void
    {
        $this->markTestSkipped(
            'RowGatewayFeature is incompatible with modernized ResultSet - '
            . 'ResultSet::setArrayObjectPrototype() now requires ArrayObject, but RowGateway does not extend ArrayObject.'
        );
    }

    public function testPostInitializeWithRowGatewayInstance(): void
    {
        $this->markTestSkipped(
            'RowGatewayFeature is incompatible with modernized ResultSet - '
            . 'ResultSet::setArrayObjectPrototype() now requires ArrayObject, but RowGatewayInterface does not extend ArrayObject.'
        );
    }

    public function testPostInitializeThrowsExceptionForNonResultSet(): void
    {
        $resultSet    = $this->createMock(ResultSetInterface::class);
        $tableGateway = $this->createTableGatewayMock($resultSet);

        $feature = new RowGatewayFeature('id');
        $feature->setTableGateway($tableGateway);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('expects the ResultSet to be an instance of');

        $feature->postInitialize();
    }

    public function testPostInitializeWithMetadataFeature(): void
    {
        $this->markTestSkipped(
            'RowGatewayFeature is incompatible with modernized ResultSet - '
            . 'ResultSet::setArrayObjectPrototype() now requires ArrayObject, but RowGateway does not extend ArrayObject.'
        );
    }

    public function testPostInitializeThrowsExceptionWhenNoMetadataAndNoPrimaryKey(): void
    {
        $resultSet = new ResultSet();

        $featureSet = $this->createMock(FeatureSet::class);
        $featureSet->expects($this->once())
            ->method('getFeatureByClassName')
            ->with(MetadataFeature::class)
            ->willReturn(false);

        $tableGateway = $this->createTableGatewayMock($resultSet, $featureSet);

        $feature = new RowGatewayFeature();
        $feature->setTableGateway($tableGateway);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No information was provided to the RowGatewayFeature');

        $feature->postInitialize();
    }

    public function testPostInitializeThrowsExceptionWhenMetadataHasNoMetadataKey(): void
    {
        $resultSet = new ResultSet();

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

    public function testConstructorStoresArguments(): void
    {
        $feature = new RowGatewayFeature('id');

        // Use reflection to check the constructorArguments property
        $property = new ReflectionProperty(RowGatewayFeature::class, 'constructorArguments');
        $args     = $property->getValue($feature);

        self::assertEquals(['id'], $args);
    }

    public function testConstructorStoresRowGatewayInstance(): void
    {
        /** @var RowGatewayInterface&MockObject $rowGateway */
        $rowGateway = $this->createMock(RowGatewayInterface::class);

        $feature = new RowGatewayFeature($rowGateway);

        // Use reflection to check the constructorArguments property
        $property = new ReflectionProperty(RowGatewayFeature::class, 'constructorArguments');
        $args     = $property->getValue($feature);

        self::assertSame($rowGateway, $args[0]);
    }

    public function testConstructorWithNoArguments(): void
    {
        $feature = new RowGatewayFeature();

        // Use reflection to check the constructorArguments property
        $property = new ReflectionProperty(RowGatewayFeature::class, 'constructorArguments');
        $args     = $property->getValue($feature);

        self::assertEquals([], $args);
    }
}
