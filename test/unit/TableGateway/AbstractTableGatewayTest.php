<?php

declare(strict_types=1);

namespace PhpDbTest\TableGateway;

use Override;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\ResultSet\ResultSet;
use PhpDb\ResultSet\ResultSetInterface;
use PhpDb\Sql;
use PhpDb\Sql\Delete;
use PhpDb\Sql\Insert;
use PhpDb\Sql\Select;
use PhpDb\Sql\Update;
use PhpDb\TableGateway\AbstractTableGateway;
use PhpDb\TableGateway\Exception\InvalidArgumentException;
use PhpDb\TableGateway\Exception\RuntimeException;
use PhpDb\TableGateway\Feature\AbstractFeature;
use PhpDb\TableGateway\Feature\FeatureSet;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[IgnoreDeprecations]
#[RequiresPhp('<= 8.6')]
#[CoversMethod(AbstractTableGateway::class, 'getTable')]
#[CoversMethod(AbstractTableGateway::class, 'getAdapter')]
#[CoversMethod(AbstractTableGateway::class, 'getSql')]
#[CoversMethod(AbstractTableGateway::class, 'getResultSetPrototype')]
#[CoversMethod(AbstractTableGateway::class, 'select')]
#[CoversMethod(AbstractTableGateway::class, 'selectWith')]
#[CoversMethod(AbstractTableGateway::class, 'executeSelect')]
#[CoversMethod(AbstractTableGateway::class, 'insert')]
#[CoversMethod(AbstractTableGateway::class, 'insertWith')]
#[CoversMethod(AbstractTableGateway::class, 'executeInsert')]
#[CoversMethod(AbstractTableGateway::class, 'update')]
#[CoversMethod(AbstractTableGateway::class, 'updateWith')]
#[CoversMethod(AbstractTableGateway::class, 'executeUpdate')]
#[CoversMethod(AbstractTableGateway::class, 'delete')]
#[CoversMethod(AbstractTableGateway::class, 'deleteWith')]
#[CoversMethod(AbstractTableGateway::class, 'executeDelete')]
#[CoversMethod(AbstractTableGateway::class, 'getLastInsertValue')]
#[CoversMethod(AbstractTableGateway::class, '__get')]
#[CoversMethod(AbstractTableGateway::class, '__set')]
#[CoversMethod(AbstractTableGateway::class, '__call')]
#[CoversMethod(AbstractTableGateway::class, '__clone')]
#[CoversMethod(AbstractTableGateway::class, 'isInitialized')]
#[CoversMethod(AbstractTableGateway::class, 'getColumns')]
#[CoversMethod(AbstractTableGateway::class, 'getFeatureSet')]
#[CoversMethod(AbstractTableGateway::class, 'initialize')]
final class AbstractTableGatewayTest extends TestCase
{
    protected MockObject&Adapter $mockAdapter;

    protected PlatformInterface&MockObject $mockPlatform;

    protected ResultInterface&MockObject $mockResultSet;
    protected MockObject&Sql\Sql $mockSql;
    protected AbstractTableGateway&MockObject $table;
    protected FeatureSet&MockObject $mockFeatureSet;
    protected MockObject&Select $mockSelect;
    protected MockObject&Insert $mockInsert;
    protected MockObject&Update $mockUpdate;
    protected MockObject&Delete $mockDelete;

    #[Override]
    protected function setUp(): void
    {
        $mockResult = $this->getMockBuilder(ResultInterface::class)->getMock();
        $mockResult->expects($this->any())->method('getAffectedRows')->willReturn(5);

        $mockPlatform = $this->getMockBuilder(PlatformInterface::class)->getMock();

        $mockResultSet = $this->getMockBuilder(ResultSetInterface::class)->getMock();

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $mockStatement->expects($this->any())->method('execute')->willReturn($mockResult);

        $mockConnection = $this->getMockBuilder(ConnectionInterface::class)->getMock();
        $mockConnection->expects($this->any())->method('getLastGeneratedValue')->willReturn(10);

        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('createStatement')->willReturn($mockStatement);
        $mockDriver->expects($this->any())->method('getConnection')->willReturn($mockConnection);

        $this->mockSelect = $this
            ->getMockBuilder(Select::class)
            ->onlyMethods(['where', 'getRawState'])
            ->setConstructorArgs(['foo'])
            ->getMock();

        $this->mockInsert = $this
            ->getMockBuilder(Insert::class)
            ->onlyMethods(['prepareStatement', 'values'])
            ->setConstructorArgs(['foo'])
            ->getMock();

        $this->mockUpdate = $this
            ->getMockBuilder(Update::class)
            ->onlyMethods(['where', 'join'])
            ->setConstructorArgs(['foo'])
            ->getMock();

        $this->mockDelete = $this->getMockBuilder(Delete::class)
            ->onlyMethods(['where'])
            ->setConstructorArgs(['foo'])
            ->getMock();

        $this->mockAdapter = $this->getMockBuilder(Adapter::class)
            ->onlyMethods([])
            ->setConstructorArgs([$mockDriver, $mockPlatform, $mockResultSet])
            ->getMock();
        $this->mockSql     = $this->getMockBuilder(Sql\Sql::class)
            ->onlyMethods(['select', 'insert', 'update', 'delete'])
            ->setConstructorArgs([$this->mockAdapter, 'foo'])
            ->getMock();
        $this->mockSql->expects($this->any())->method('select')->willReturn($this->mockSelect);
        $this->mockSql->expects($this->any())->method('insert')->willReturn($this->mockInsert);
        $this->mockSql->expects($this->any())->method('update')->willReturn($this->mockUpdate);
        $this->mockSql->expects($this->any())->method('delete')->willReturn($this->mockDelete);

        $this->mockFeatureSet = $this->getMockBuilder(FeatureSet::class)->getMock();

        $this->table = $this
            ->getMockBuilder(AbstractTableGateway::class)
            ->onlyMethods([])
            ->getMock();

        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        foreach ($tgReflection->getProperties() as $tgPropReflection) {
            /** @noinspection PhpExpressionResultUnusedInspection */
            $tgPropReflection->setAccessible(true);
            switch ($tgPropReflection->getName()) {
                case 'table':
                    $tgPropReflection->setValue($this->table, 'foo');
                    break;
                case 'adapter':
                    $tgPropReflection->setValue($this->table, $this->mockAdapter);
                    break;
                case 'resultSetPrototype':
                    $tgPropReflection->setValue($this->table, new ResultSet());
                    break;
                case 'sql':
                    $tgPropReflection->setValue($this->table, $this->mockSql);
                    break;
                case 'featureSet':
                    $tgPropReflection->setValue($this->table, $this->mockFeatureSet);
                    break;
            }
            /** @noinspection PhpExpressionResultUnusedInspection */
            $tgPropReflection->setAccessible(false);
        }
    }

    public function testGetTable(): void
    {
        self::assertEquals('foo', $this->table->getTable());
    }

    public function testGetAdapter(): void
    {
        self::assertSame($this->mockAdapter, $this->table->getAdapter());
    }

    public function testGetSql(): void
    {
        self::assertInstanceOf(Sql\Sql::class, $this->table->getSql());
    }

    public function testGetSelectResultPrototype(): void
    {
        self::assertInstanceOf(ResultSet::class, $this->table->getResultSetPrototype());
    }

    public function testSelectWithNoWhere(): void
    {
        $resultSet = $this->table->select();

        // check return types
        self::assertInstanceOf(ResultSet::class, $resultSet);
        self::assertNotSame($this->table->getResultSetPrototype(), $resultSet);
    }

    public function testSelectWithWhereString(): void
    {
        $mockSelect = $this->mockSelect;
        $mockSelect->expects($this->any())
            ->method('getRawState')
            ->willReturn([
                'table'   => $this->table->getTable(),
                'columns' => [],
            ]);

        // assert select::from() is called
        $mockSelect->expects($this->once())
            ->method('where')
            ->with($this->equalTo('foo'));

        $this->table->select('foo');
    }

    public function testSelectWithArrayTable(): void
    {
        // Case 1
        $select1 = $this->getMockBuilder(Select::class)->onlyMethods(['getRawState'])->getMock();
        $select1->expects($this->once())
            ->method('getRawState')
            ->willReturn([
                'table'   => 'foo', // Standard table name format, valid according to Select::from()
                'columns' => null,
            ]);
        $return = $this->table->selectWith($select1);
        $this->assertInstanceOf(ResultSet::class, $return);

        // Case 2
        $select1 = $this->getMockBuilder(Select::class)->onlyMethods(['getRawState'])->getMock();
        $select1->expects($this->once())
            ->method('getRawState')
            ->willReturn([
                'table'   => ['f' => 'foo'], // Alias table name format, valid according to Select::from()
                'columns' => null,
            ]);
        $return = $this->table->selectWith($select1);
        $this->assertInstanceOf(ResultSet::class, $return);
    }

    public function testInsert(): void
    {
        $mockInsert = $this->mockInsert;

        $mockInsert->expects($this->once())
            ->method('prepareStatement')
            ->with($this->mockAdapter);

        $mockInsert->expects($this->once())
            ->method('values')
            ->with($this->equalTo(['foo' => 'bar']));

        $affectedRows = $this->table->insert(['foo' => 'bar']);
        self::assertEquals(5, $affectedRows);
    }

    public function testUpdate(): void
    {
        $mockUpdate = $this->mockUpdate;

        // assert select::from() is called
        $mockUpdate->expects($this->once())
            ->method('where')
            ->with($this->equalTo('id = 2'));

        $affectedRows = $this->table->update(['foo' => 'bar'], 'id = 2');
        self::assertEquals(5, $affectedRows);
    }

    public function testUpdateWithJoin(): void
    {
        $mockUpdate = $this->mockUpdate;

        $joins = [
            [
                'name' => 'baz',
                'on'   => 'foo.fooId = baz.fooId',
                'type' => Sql\Join::JOIN_LEFT,
            ],
        ];

        // assert select::from() is called
        $mockUpdate->expects($this->once())
            ->method('where')
            ->with($this->equalTo('id = 2'));

        $mockUpdate->expects($this->once())
            ->method('join')
            ->with($joins[0]['name'], $joins[0]['on'], $joins[0]['type']);

        $affectedRows = $this->table->update(['foo.field' => 'bar'], 'id = 2', $joins);
        self::assertEquals(5, $affectedRows);
    }

    public function testUpdateWithJoinDefaultType(): void
    {
        $mockUpdate = $this->mockUpdate;

        $joins = [
            [
                'name' => 'baz',
                'on'   => 'foo.fooId = baz.fooId',
            ],
        ];

        // assert select::from() is called
        $mockUpdate->expects($this->once())
            ->method('where')
            ->with($this->equalTo('id = 2'));

        $mockUpdate->expects($this->once())
            ->method('join')
            ->with($joins[0]['name'], $joins[0]['on'], Sql\Join::JOIN_INNER);

        $affectedRows = $this->table->update(['foo.field' => 'bar'], 'id = 2', $joins);
        self::assertEquals(5, $affectedRows);
    }

    public function testUpdateWithNoCriteria(): void
    {
        /** @phpstan-ignore expr.resultUnused */
        $this->mockUpdate;

        $affectedRows = $this->table->update(['foo' => 'bar']);
        self::assertEquals(5, $affectedRows);
    }

    public function testDelete(): void
    {
        $mockDelete = $this->mockDelete;

        // assert select::from() is called
        $mockDelete->expects($this->once())
            ->method('where')
            ->with($this->equalTo('foo'));

        $affectedRows = $this->table->delete('foo');
        self::assertEquals(5, $affectedRows);
    }

    public function testGetLastInsertValue(): void
    {
        $this->table->insert(['foo' => 'bar']);
        self::assertEquals(10, $this->table->getLastInsertValue());
    }

    public function testInitializeBuildsAResultSet(): void
    {
        $stub = $this
            ->getMockBuilder(AbstractTableGateway::class)
            ->onlyMethods([])
            ->getMock();

        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        foreach ($tgReflection->getProperties() as $tgPropReflection) {
            switch ($tgPropReflection->getName()) {
                case 'table':
                    $tgPropReflection->setValue($stub, 'foo');
                    break;
                case 'adapter':
                    $tgPropReflection->setValue($stub, $this->mockAdapter);
                    break;
                case 'featureSet':
                    $tgPropReflection->setValue($stub, $this->mockFeatureSet);
                    break;
            }
        }

        $stub->initialize();
        $this->assertInstanceOf(ResultSet::class, $stub->getResultSetPrototype());
    }

    // @codingStandardsIgnoreStart
    public function test__get(): void
    {
        // @codingStandardsIgnoreEnd
        $this->table->insert(['foo']); // trigger last insert id update

        self::assertEquals(10, $this->table->lastInsertValue);
        self::assertSame($this->mockAdapter, $this->table->adapter);
        //self::assertEquals('foo', $this->table->table);
    }

    // @codingStandardsIgnoreStart
    public function test__clone(): void
    {
        // @codingStandardsIgnoreEnd
        $cTable = clone $this->table;
        self::assertSame($this->mockAdapter, $cTable->getAdapter());
    }

    public function testIsInitialized(): void
    {
        // Create a fresh mock without initialization
        $stub = $this->getMockBuilder(AbstractTableGateway::class)
            ->onlyMethods([])
            ->getMock();

        self::assertFalse($stub->isInitialized());

        // Set required properties for initialization
        $tgReflection = new ReflectionClass(AbstractTableGateway::class);

        $tableProp = $tgReflection->getProperty('table');
        $tableProp->setValue($stub, 'foo');

        $adapterProp = $tgReflection->getProperty('adapter');
        $adapterProp->setValue($stub, $this->mockAdapter);

        $stub->initialize();

        self::assertTrue($stub->isInitialized());
    }

    public function testInitializeEarlyReturnWhenAlreadyInitialized(): void
    {
        // Create a fresh mock without initialization
        $stub = $this->getMockBuilder(AbstractTableGateway::class)
            ->onlyMethods([])
            ->getMock();

        // Set required properties for initialization
        $tgReflection = new ReflectionClass(AbstractTableGateway::class);

        $tableProp = $tgReflection->getProperty('table');
        $tableProp->setValue($stub, 'foo');

        $adapterProp = $tgReflection->getProperty('adapter');
        $adapterProp->setValue($stub, $this->mockAdapter);

        // First initialization
        $stub->initialize();
        self::assertTrue($stub->isInitialized());

        // Get the featureSet that was created during first init
        $featureSetProp     = $tgReflection->getProperty('featureSet');
        $originalFeatureSet = $featureSetProp->getValue($stub);

        // Second initialization should early return (line 69)
        $stub->initialize();

        // Verify featureSet is still the same object (proving early return was taken)
        self::assertSame($originalFeatureSet, $featureSetProp->getValue($stub));
    }

    public function testInitializeThrowsExceptionWithoutAdapter(): void
    {
        $stub = $this->getMockBuilder(AbstractTableGateway::class)
            ->onlyMethods([])
            ->getMock();

        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        $tableProp    = $tgReflection->getProperty('table');
        $tableProp->setValue($stub, 'foo');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This table does not have an Adapter setup');

        $stub->initialize();
    }

    public function testInitializeThrowsExceptionWithoutTable(): void
    {
        $stub = $this->getMockBuilder(AbstractTableGateway::class)
            ->onlyMethods([])
            ->getMock();

        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        $adapterProp  = $tgReflection->getProperty('adapter');
        $adapterProp->setValue($stub, $this->mockAdapter);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This table object does not have a valid table set.');

        $stub->initialize();
    }

    public function testGetColumns(): void
    {
        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        $columnsProp  = $tgReflection->getProperty('columns');
        $columnsProp->setValue($this->table, ['id', 'name', 'email']);

        self::assertEquals(['id', 'name', 'email'], $this->table->getColumns());
    }

    public function testGetFeatureSet(): void
    {
        self::assertSame($this->mockFeatureSet, $this->table->getFeatureSet());
    }

    public function testSelectWithClosure(): void
    {
        $mockSelect = $this->mockSelect;
        $mockSelect->expects($this->any())
            ->method('getRawState')
            ->willReturn([
                'table'   => $this->table->getTable(),
                'columns' => [],
            ]);

        $closureCalled = false;
        $result        = $this->table->select(function ($select) use (&$closureCalled) {
            $closureCalled = true;
            self::assertInstanceOf(Select::class, $select);
        });

        self::assertTrue($closureCalled);
        self::assertInstanceOf(ResultSet::class, $result);
    }

    public function testInsertWith(): void
    {
        $insert = new Insert('foo');
        $insert->values(['column' => 'value']);

        $affectedRows = $this->table->insertWith($insert);
        self::assertEquals(5, $affectedRows);
    }

    public function testUpdateWith(): void
    {
        $update = $this->getMockBuilder(Update::class)
            ->onlyMethods(['getRawState'])
            ->setConstructorArgs(['foo'])
            ->getMock();

        $update->expects($this->any())
            ->method('getRawState')
            ->willReturn(['table' => 'foo']);

        $affectedRows = $this->table->updateWith($update);
        self::assertEquals(5, $affectedRows);
    }

    public function testDeleteWith(): void
    {
        $delete = $this->getMockBuilder(Delete::class)
            ->onlyMethods(['getRawState'])
            ->setConstructorArgs(['foo'])
            ->getMock();

        $delete->expects($this->any())
            ->method('getRawState')
            ->willReturn(['table' => 'foo']);

        $affectedRows = $this->table->deleteWith($delete);
        self::assertEquals(5, $affectedRows);
    }

    public function testDeleteWithClosure(): void
    {
        // The closure receives the Delete object created by $this->sql->delete()
        // We verify that the closure is called with a Delete instance
        $closureCalled = false;
        $affectedRows  = $this->table->delete(function ($delete) use (&$closureCalled) {
            $closureCalled = true;
            self::assertInstanceOf(Delete::class, $delete);
        });

        self::assertTrue($closureCalled);
        self::assertEquals(5, $affectedRows);
    }

    // @codingStandardsIgnoreStart
    public function test__getTable(): void
    {
        // @codingStandardsIgnoreEnd
        self::assertEquals('foo', $this->table->table);
    }

    // @codingStandardsIgnoreStart
    public function test__getThrowsExceptionForInvalidProperty(): void
    {
        // @codingStandardsIgnoreEnd
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid magic property access');

        /** @phpstan-ignore expr.resultUnused, property.notFound */
        $this->table->invalidProperty;
    }

    // @codingStandardsIgnoreStart
    public function test__setThrowsExceptionForInvalidProperty(): void
    {
        // @codingStandardsIgnoreEnd
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid magic property access');

        /** @phpstan-ignore property.notFound */
        $this->table->invalidProperty = 'value';
    }

    // @codingStandardsIgnoreStart
    public function test__callThrowsExceptionForInvalidMethod(): void
    {
        // @codingStandardsIgnoreEnd
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid method (invalidMethod) called');

        /** @phpstan-ignore method.notFound */
        $this->table->invalidMethod();
    }

    // @codingStandardsIgnoreStart
    public function test__cloneWithTableIdentifier(): void
    {
        // @codingStandardsIgnoreEnd
        $tableIdentifier = new Sql\TableIdentifier('bar', 'schema');

        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        $tableProp    = $tgReflection->getProperty('table');
        $tableProp->setValue($this->table, $tableIdentifier);

        $cloned = clone $this->table;

        // The table should be cloned, not the same instance
        self::assertNotSame($tableIdentifier, $cloned->getTable());
        self::assertEquals($tableIdentifier->getTable(), $cloned->getTable()->getTable());
    }

    // @codingStandardsIgnoreStart
    public function test__cloneWithAliasedTableIdentifier(): void
    {
        // @codingStandardsIgnoreEnd
        $tableIdentifier = new Sql\TableIdentifier('bar', 'schema');
        $aliasedTable    = ['alias' => $tableIdentifier];

        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        $tableProp    = $tgReflection->getProperty('table');
        $tableProp->setValue($this->table, $aliasedTable);

        $cloned = clone $this->table;

        $clonedTable = $cloned->getTable();
        self::assertIsArray($clonedTable);
        // The TableIdentifier inside the array should be cloned
        self::assertNotSame($tableIdentifier, $clonedTable['alias']);
    }

    public function testExecuteSelectThrowsExceptionWhenArrayTableDoesNotMatch(): void
    {
        $select = $this->getMockBuilder(Select::class)
            ->onlyMethods(['getRawState'])
            ->setConstructorArgs(['bar'])
            ->getMock();

        // With an array table that doesn't end with 'foo', exception should be thrown
        $select->expects($this->any())
            ->method('getRawState')
            ->willReturn([
                'table'   => ['alias' => 'bar'],
                'columns' => [Select::SQL_STAR],
            ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The table name of the provided Select object must match that of the table');

        $this->table->selectWith($select);
    }

    public function testExecuteInsertThrowsExceptionWhenTableDoesNotMatch(): void
    {
        $insert = new Insert('bar');
        $insert->values(['name' => 'test']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The table name of the provided Insert object must match that of the table');

        $this->table->insertWith($insert);
    }

    public function testExecuteUpdateThrowsExceptionWhenTableDoesNotMatch(): void
    {
        $update = new Update('bar');
        $update->set(['name' => 'test']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The table name of the provided Update object must match that of the table');

        $this->table->updateWith($update);
    }

    public function testExecuteDeleteThrowsExceptionWhenTableDoesNotMatch(): void
    {
        $delete = new Delete('bar');
        $delete->where(['id' => 1]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The table name of the provided Delete object must match that of the table');

        $this->table->deleteWith($delete);
    }

    public function testSelectAppliesColumnsWhenStarSelected(): void
    {
        // Set up columns on the table
        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        $columnsProp  = $tgReflection->getProperty('columns');
        $columnsProp->setValue($this->table, ['id', 'name', 'email']);

        $select = $this->getMockBuilder(Select::class)
            ->onlyMethods(['getRawState', 'columns'])
            ->setConstructorArgs(['foo'])
            ->getMock();

        $select->expects($this->any())
            ->method('getRawState')
            ->willReturn([
                'table'   => 'foo',
                'columns' => [Select::SQL_STAR],
            ]);

        $select->expects($this->once())
            ->method('columns')
            ->with(['id', 'name', 'email']);

        $this->table->selectWith($select);
    }

    // @codingStandardsIgnoreStart
    public function test__getLastInsertValue(): void
    {
        // @codingStandardsIgnoreEnd
        self::assertNull($this->table->lastInsertValue);
    }

    // @codingStandardsIgnoreStart
    public function test__getAdapter(): void
    {
        // @codingStandardsIgnoreEnd
        self::assertSame($this->mockAdapter, $this->table->adapter);
    }

    // @codingStandardsIgnoreStart
    public function test__getWithFeatureSetMagicGet(): void
    {
        // @codingStandardsIgnoreEnd
        // Create a custom feature that can handle magic get
        $feature = new class extends AbstractFeature {
            /**
             * @return array<string, array<int, string>>
             * @phpstan-ignore method.childReturnType
             */
            public function getMagicMethodSpecifications(): array
            {
                return ['get' => ['customProperty']];
            }
        };

        // Create a FeatureSet mock that returns true for canCallMagicGet
        $featureSet = $this->getMockBuilder(FeatureSet::class)
            ->onlyMethods(['canCallMagicGet', 'callMagicGet'])
            ->getMock();
        $featureSet->expects($this->once())
            ->method('canCallMagicGet')
            ->with('customProperty')
            ->willReturn(true);
        $featureSet->expects($this->once())
            ->method('callMagicGet')
            ->with('customProperty')
            ->willReturn('customValue');

        $tgReflection   = new ReflectionClass(AbstractTableGateway::class);
        $featureSetProp = $tgReflection->getProperty('featureSet');
        $featureSetProp->setValue($this->table, $featureSet);

        /** @phpstan-ignore property.notFound */
        $result = $this->table->customProperty;

        self::assertEquals('customValue', $result);
    }

    // @codingStandardsIgnoreStart
    public function test__setWithFeatureSetMagicSet(): void
    {
        // @codingStandardsIgnoreEnd
        // Create a FeatureSet mock that returns true for canCallMagicSet
        $featureSet = $this->getMockBuilder(FeatureSet::class)
            ->onlyMethods(['canCallMagicSet', 'callMagicSet'])
            ->getMock();
        $featureSet->expects($this->once())
            ->method('canCallMagicSet')
            ->with('customProperty')
            ->willReturn(true);
        $featureSet->expects($this->once())
            ->method('callMagicSet')
            ->with('customProperty', 'customValue');

        $tgReflection   = new ReflectionClass(AbstractTableGateway::class);
        $featureSetProp = $tgReflection->getProperty('featureSet');
        $featureSetProp->setValue($this->table, $featureSet);

        /** @phpstan-ignore property.notFound */
        $this->table->customProperty = 'customValue';
    }

    // @codingStandardsIgnoreStart
    public function test__callWithFeatureSetMagicCall(): void
    {
        // @codingStandardsIgnoreEnd
        // Create a FeatureSet mock that returns true for canCallMagicCall
        $featureSet = $this->getMockBuilder(FeatureSet::class)
            ->onlyMethods(['canCallMagicCall', 'callMagicCall'])
            ->getMock();
        $featureSet->expects($this->once())
            ->method('canCallMagicCall')
            ->with('customMethod')
            ->willReturn(true);
        $featureSet->expects($this->once())
            ->method('callMagicCall')
            ->with('customMethod', ['arg1', 'arg2'])
            ->willReturn('customResult');

        $tgReflection   = new ReflectionClass(AbstractTableGateway::class);
        $featureSetProp = $tgReflection->getProperty('featureSet');
        $featureSetProp->setValue($this->table, $featureSet);

        /** @phpstan-ignore method.notFound */
        $result = $this->table->customMethod('arg1', 'arg2');

        self::assertEquals('customResult', $result);
    }
}
