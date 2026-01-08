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
#[CoversMethod(AbstractTableGateway::class, '__clone')]
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

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        // mock the adapter, driver, and parts
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
        $this->markTestSkipped('This needs refactored due to setAccessible has been deprecated in PHP 8.1');
        /** @phpstan-ignore deadCode.unreachable */
        $stub = $this
            ->getMockBuilder(AbstractTableGateway::class)
            ->onlyMethods([])
            ->getMock();

        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        foreach ($tgReflection->getProperties() as $tgPropReflection) {
            /** @noinspection PhpExpressionResultUnusedInspection */
            $tgPropReflection->setAccessible(true);
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

    public function testInitializeThrowsExceptionWithoutAdapter(): void
    {
        $stub = $this->getMockBuilder(AbstractTableGateway::class)
            ->onlyMethods([])
            ->getMock();

        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        $tableProp = $tgReflection->getProperty('table');
        $tableProp->setValue($stub, 'foo');

        $this->expectException(\PhpDb\TableGateway\Exception\RuntimeException::class);
        $this->expectExceptionMessage('This table does not have an Adapter setup');

        $stub->initialize();
    }

    public function testInitializeThrowsExceptionWithoutTable(): void
    {
        $stub = $this->getMockBuilder(AbstractTableGateway::class)
            ->onlyMethods([])
            ->getMock();

        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        $adapterProp = $tgReflection->getProperty('adapter');
        $adapterProp->setValue($stub, $this->mockAdapter);

        $this->expectException(\PhpDb\TableGateway\Exception\RuntimeException::class);
        $this->expectExceptionMessage('This table object does not have a valid table set.');

        $stub->initialize();
    }

    public function testGetColumns(): void
    {
        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        $columnsProp = $tgReflection->getProperty('columns');
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
        $result = $this->table->select(function ($select) use (&$closureCalled) {
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
        $affectedRows = $this->table->delete(function ($delete) use (&$closureCalled) {
            $closureCalled = true;
            self::assertInstanceOf(Delete::class, $delete);
        });

        self::assertTrue($closureCalled);
        self::assertEquals(5, $affectedRows);
    }

    public function test__getTable(): void
    {
        self::assertEquals('foo', $this->table->table);
    }

    public function test__getThrowsExceptionForInvalidProperty(): void
    {
        $this->expectException(\PhpDb\TableGateway\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid magic property access');

        /** @phpstan-ignore expr.resultUnused */
        $this->table->invalidProperty;
    }

    public function test__setThrowsExceptionForInvalidProperty(): void
    {
        $this->expectException(\PhpDb\TableGateway\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid magic property access');

        $this->table->invalidProperty = 'value';
    }

    public function test__callThrowsExceptionForInvalidMethod(): void
    {
        $this->expectException(\PhpDb\TableGateway\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid method (invalidMethod) called');

        $this->table->invalidMethod();
    }

    public function test__cloneWithTableIdentifier(): void
    {
        $tableIdentifier = new Sql\TableIdentifier('bar', 'schema');

        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        $tableProp = $tgReflection->getProperty('table');
        $tableProp->setValue($this->table, $tableIdentifier);

        $cloned = clone $this->table;

        // The table should be cloned, not the same instance
        self::assertNotSame($tableIdentifier, $cloned->getTable());
        self::assertEquals($tableIdentifier->getTable(), $cloned->getTable()->getTable());
    }

    public function test__cloneWithAliasedTableIdentifier(): void
    {
        $tableIdentifier = new Sql\TableIdentifier('bar', 'schema');
        $aliasedTable = ['alias' => $tableIdentifier];

        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        $tableProp = $tgReflection->getProperty('table');
        $tableProp->setValue($this->table, $aliasedTable);

        $cloned = clone $this->table;

        $clonedTable = $cloned->getTable();
        self::assertIsArray($clonedTable);
        // The TableIdentifier inside the array should be cloned
        self::assertNotSame($tableIdentifier, $clonedTable['alias']);
    }
}
