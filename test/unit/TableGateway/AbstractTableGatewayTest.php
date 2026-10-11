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
use PhpDb\TableGateway\Feature\EventFeatureEventsInterface;
use PhpDb\TableGateway\Feature\FeatureSet;
use PhpDbTest\TableGateway\Feature\TestAsset\TestTableGatewayFeature;
use PhpDbTest\TableGateway\TestAsset\HookRecordingTableGateway;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function count;
use function sprintf;

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
#[CoversMethod(AbstractTableGateway::class, 'unaliasTable')]
#[CoversMethod(AbstractTableGateway::class, 'cloneTable')]
#[CoversMethod(AbstractTableGateway::class, 'requireFeatureSet')]
#[CoversMethod(AbstractTableGateway::class, 'requireResultSetPrototype')]
#[CoversMethod(AbstractTableGateway::class, 'requireSql')]
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

    /** @return array<string, array{string, list<mixed>}> */
    public static function executeHookProvider(): array
    {
        return [
            'delete' => ['delete', ['id = 1']],
            'insert' => ['insert', [['foo' => 'bar']]],
            'select' => ['select', []],
            'update' => ['update', [['foo' => 'bar']]],
        ];
    }

    /** @return array<string, array{string, list<mixed>, list<array{string, int}>}> */
    public static function featureEventProvider(): array
    {
        return [
            'delete' => [
                'delete',
                ['id = 1'],
                [
                    [EventFeatureEventsInterface::EVENT_PRE_DELETE,  1],
                    [EventFeatureEventsInterface::EVENT_POST_DELETE, 2],
                ],
            ],
            'insert' => [
                'insert',
                [['foo' => 'bar']],
                [
                    [EventFeatureEventsInterface::EVENT_PRE_INSERT,  1],
                    [EventFeatureEventsInterface::EVENT_POST_INSERT, 2],
                ],
            ],
            'select' => [
                'select',
                [],
                [
                    [EventFeatureEventsInterface::EVENT_PRE_SELECT,  1],
                    [EventFeatureEventsInterface::EVENT_POST_SELECT, 3],
                ],
            ],
            'update' => [
                'update',
                [['foo' => 'bar']],
                [
                    [EventFeatureEventsInterface::EVENT_PRE_UPDATE,  1],
                    [EventFeatureEventsInterface::EVENT_POST_UPDATE, 2],
                ],
            ],
        ];
    }

    /** @return array<string, array{string, list<mixed>}> */
    public static function initializingOperationProvider(): array
    {
        return [
            'delete'                => ['delete', ['id = 1']],
            'deleteWith'            => ['deleteWith', [new Delete('foo')]],
            'getFeatureSet'         => ['getFeatureSet', []],
            'getResultSetPrototype' => ['getResultSetPrototype', []],
            'getSql'                => ['getSql', []],
            'insert'                => ['insert', [['foo' => 'bar']]],
            'insertWith'            => ['insertWith', [(new Insert('foo'))->values(['foo' => 'bar'])]],
            'select'                => ['select', []],
            'selectWith'            => ['selectWith', [new Select('foo')]],
            'update'                => ['update', [['foo' => 'bar']]],
            'updateWith'            => ['updateWith', [new Update('foo')]],
        ];
    }

    /** @return array<string, array{string, list<mixed>, string}> */
    public static function statementWithoutResultProvider(): array
    {
        return [
            'delete' => ['delete', [['id' => 1]], 'Delete'],
            'insert' => ['insert', [['foo' => 'bar']], 'Insert'],
            'select' => ['select', [], 'Select'],
            'update' => ['update', [['foo' => 'bar']], 'Update'],
        ];
    }

    /** @return array<string, array{string, string}> */
    public static function uninitializedPropertyProvider(): array
    {
        return [
            'feature set'          => ['featureSet', 'getFeatureSet'],
            'result set prototype' => ['resultSetPrototype', 'getResultSetPrototype'],
            'sql'                  => ['sql', 'getSql'],
        ];
    }

    #[Test]
    public function cloneCopiesTheSqlAndResultSetPrototype(): void
    {
        $cloned = clone $this->table;

        static::assertNotSame($this->table->getSql(), $cloned->getSql());
        static::assertNotSame($this->table->getResultSetPrototype(), $cloned->getResultSetPrototype());
    }

    #[Test]
    public function cloneKeepsTheAdapter(): void
    {
        $cTable = clone $this->table;
        static::assertSame($this->mockAdapter, $cTable->getAdapter());
    }

    #[Test]
    public function cloneOfAGatewayWithoutDependenciesKeepsThemUnset(): void
    {
        $gateway = $this->getMockBuilder(AbstractTableGateway::class)->onlyMethods([])->getMock();

        $cloned = clone $gateway;

        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        static::assertNull($tgReflection->getProperty('sql')->getValue($cloned));
        static::assertNull($tgReflection->getProperty('resultSetPrototype')->getValue($cloned));
        static::assertNull($tgReflection->getProperty('table')->getValue($cloned));
    }

    #[Test]
    public function cloneWithAnAliasedTableIdentifier(): void
    {
        $tableIdentifier = new Sql\TableIdentifier('bar', 'schema');
        $aliasedTable    = ['alias' => $tableIdentifier];

        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        $tableProp    = $tgReflection->getProperty('table');
        $tableProp->setValue($this->table, $aliasedTable);

        $cloned = clone $this->table;

        $clonedTable = $cloned->getTable();
        static::assertIsArray($clonedTable);
        // The TableIdentifier inside the array should be cloned
        static::assertNotSame($tableIdentifier, $clonedTable['alias']);
    }

    #[Test]
    public function cloneWithATableIdentifier(): void
    {
        $tableIdentifier = new Sql\TableIdentifier('bar', 'schema');

        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        $tableProp    = $tgReflection->getProperty('table');
        $tableProp->setValue($this->table, $tableIdentifier);

        $cloned = clone $this->table;

        // The table should be cloned, not the same instance
        static::assertNotSame($tableIdentifier, $cloned->getTable());
        static::assertEquals($tableIdentifier->getTable(), $cloned->getTable()->getTable());
    }

    #[Test]
    public function delete(): void
    {
        $mockDelete = $this->mockDelete;

        // assert select::from() is called
        $mockDelete->expects($this->once())
            ->method('where')
            ->with(static::equalTo('foo'));

        $affectedRows = $this->table->delete('foo');
        static::assertSame(5, $affectedRows);
    }

    #[Test]
    public function deleteWith(): void
    {
        $delete = $this->getMockBuilder(Delete::class)
            ->onlyMethods(['getRawState'])
            ->setConstructorArgs(['foo'])
            ->getMock();

        $delete->expects($this->any())
            ->method('getRawState')
            ->willReturnMap([['table', 'foo']]);

        $affectedRows = $this->table->deleteWith($delete);
        static::assertSame(5, $affectedRows);
    }

    #[Test]
    public function deleteWithClosure(): void
    {
        // The closure receives the Delete object created by $this->sql->delete()
        // We verify that the closure is called with a Delete instance
        $this->mockDelete->expects($this->never())->method('where');

        $closureCalled = false;
        $affectedRows  = $this->table->delete(static function ($delete) use (&$closureCalled) {
            $closureCalled = true;
            self::assertInstanceOf(Delete::class, $delete);
        });

        static::assertTrue($closureCalled);
        static::assertSame(5, $affectedRows);
    }

    #[Test]
    public function executeDeleteThrowsExceptionWhenTableDoesNotMatch(): void
    {
        $delete = new Delete('bar');
        $delete->where(['id' => 1]);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(sprintf(RuntimeException::TABLE_MISMATCH, 'Delete'));

        $this->table->deleteWith($delete);
    }

    #[Test]
    public function executeInsertThrowsExceptionWhenTableDoesNotMatch(): void
    {
        $insert = new Insert('bar');
        $insert->values(['name' => 'test']);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(sprintf(RuntimeException::TABLE_MISMATCH, 'Insert'));

        $this->table->insertWith($insert);
    }

    #[Test]
    public function executeSelectThrowsExceptionWhenArrayTableDoesNotMatch(): void
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

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(sprintf(RuntimeException::TABLE_MISMATCH, 'Select'));

        $this->table->selectWith($select);
    }

    #[Test]
    public function executeUpdateThrowsExceptionWhenTableDoesNotMatch(): void
    {
        $update = new Update('bar');
        $update->set(['name' => 'test']);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(sprintf(RuntimeException::TABLE_MISMATCH, 'Update'));

        $this->table->updateWith($update);
    }

    #[Test]
    public function getAdapter(): void
    {
        static::assertSame($this->mockAdapter, $this->table->getAdapter());
    }

    #[Test]
    public function getAdapterThrowsWhenNoAdapterIsSet(): void
    {
        $gateway = $this->getMockBuilder(AbstractTableGateway::class)->onlyMethods([])->getMock();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(RuntimeException::MISSING_ADAPTER);

        $gateway->getAdapter();
    }

    #[Test]
    public function getColumns(): void
    {
        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        $columnsProp  = $tgReflection->getProperty('columns');
        $columnsProp->setValue($this->table, ['id', 'name', 'email']);

        static::assertEquals(['id', 'name', 'email'], $this->table->getColumns());
    }

    #[Test]
    public function getFeatureSet(): void
    {
        static::assertSame($this->mockFeatureSet, $this->table->getFeatureSet());
    }

    #[Test]
    public function getLastInsertValue(): void
    {
        $this->table->insert(['foo' => 'bar']);
        static::assertSame(10, $this->table->getLastInsertValue());
    }

    #[Test]
    public function getSelectResultPrototype(): void
    {
        static::assertInstanceOf(ResultSet::class, $this->table->getResultSetPrototype());
    }

    #[Test]
    public function getSql(): void
    {
        static::assertInstanceOf(Sql\Sql::class, $this->table->getSql());
    }

    #[Test]
    public function getTable(): void
    {
        static::assertSame('foo', $this->table->getTable());
    }

    #[Test]
    public function getTableThrowsWhenNoTableIsSet(): void
    {
        $gateway = $this->getMockBuilder(AbstractTableGateway::class)->onlyMethods([])->getMock();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(RuntimeException::MISSING_TABLE);

        $gateway->getTable();
    }

    #[Test]
    public function initializeAppliesItsFeatureEventsOnlyOnce(): void
    {
        $events     = [];
        $featureSet = $this->getMockBuilder(FeatureSet::class)->onlyMethods(['apply'])->getMock();
        $featureSet->method('apply')
            ->willReturnCallback(
                static function (string $event) use (&$events): void {
                    $events[] = $event;
                },
            );

        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        $tgReflection->getProperty('featureSet')->setValue($this->table, $featureSet);

        $this->table->initialize();
        $this->table->initialize();

        static::assertSame(
            [EventFeatureEventsInterface::EVENT_PRE_INITIALIZE, EventFeatureEventsInterface::EVENT_POST_INITIALIZE],
            $events,
        );
    }

    #[Test]
    public function initializeBuildsAResultSet(): void
    {
        $stub = $this->getMockBuilder(AbstractTableGateway::class)
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
        static::assertInstanceOf(ResultSet::class, $stub->getResultSetPrototype());
    }

    #[Test]
    public function initializeEarlyReturnWhenAlreadyInitialized(): void
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
        static::assertTrue($stub->isInitialized());

        // Get the featureSet that was created during first init
        $featureSetProp     = $tgReflection->getProperty('featureSet');
        $originalFeatureSet = $featureSetProp->getValue($stub);

        // Second initialization should early return (line 69)
        $stub->initialize();

        // Verify featureSet is still the same object (proving early return was taken)
        static::assertSame($originalFeatureSet, $featureSetProp->getValue($stub));
    }

    #[Test]
    public function initializeThrowsExceptionWithoutAdapter(): void
    {
        $stub = $this->getMockBuilder(AbstractTableGateway::class)
            ->onlyMethods([])
            ->getMock();

        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        $tableProp    = $tgReflection->getProperty('table');
        $tableProp->setValue($stub, 'foo');

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(RuntimeException::MISSING_ADAPTER);

        $stub->initialize();
    }

    #[Test]
    public function initializeThrowsExceptionWithoutTable(): void
    {
        $stub = $this->getMockBuilder(AbstractTableGateway::class)
            ->onlyMethods([])
            ->getMock();

        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        $adapterProp  = $tgReflection->getProperty('adapter');
        $adapterProp->setValue($stub, $this->mockAdapter);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(RuntimeException::MISSING_TABLE);

        $stub->initialize();
    }

    #[Test]
    public function insert(): void
    {
        $mockInsert = $this->mockInsert;

        $mockInsert->expects($this->once())
            ->method('prepareStatement')
            ->with($this->mockAdapter);

        $mockInsert->expects($this->once())
            ->method('values')
            ->with(static::equalTo(['foo' => 'bar']));

        $affectedRows = $this->table->insert(['foo' => 'bar']);
        static::assertSame(5, $affectedRows);
    }

    #[Test]
    public function insertWith(): void
    {
        $insert = new Insert('foo');
        $insert->values(['column' => 'value']);

        $affectedRows = $this->table->insertWith($insert);
        static::assertSame(5, $affectedRows);
    }

    #[Test]
    public function isInitialized(): void
    {
        // Create a fresh mock without initialization
        $stub = $this->getMockBuilder(AbstractTableGateway::class)
            ->onlyMethods([])
            ->getMock();

        static::assertFalse($stub->isInitialized());

        // Set required properties for initialization
        $tgReflection = new ReflectionClass(AbstractTableGateway::class);

        $tableProp = $tgReflection->getProperty('table');
        $tableProp->setValue($stub, 'foo');

        $adapterProp = $tgReflection->getProperty('adapter');
        $adapterProp->setValue($stub, $this->mockAdapter);

        $stub->initialize();

        static::assertTrue($stub->isInitialized());
    }

    #[Test]
    public function magicCallDelegatesToTheFeatureSet(): void
    {
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

        static::assertSame('customResult', $result);
    }

    #[Test]
    public function magicCallThrowsForUnknownMethod(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(
            sprintf(InvalidArgumentException::INVALID_MAGIC_CALL, 'invalidMethod', $this->table::class),
        );

        /** @phpstan-ignore method.notFound */
        $this->table->invalidMethod();
    }

    #[Test]
    public function magicGetDelegatesToTheFeatureSet(): void
    {
        // Create a custom feature that can handle magic get
        $feature                   = new TestTableGatewayFeature();
        $feature->magicMethodSpecs = ['get' => ['customProperty']];

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

        static::assertSame('customValue', $result);
    }

    #[Test]
    public function magicGetReturnsTheAdapter(): void
    {
        static::assertSame($this->mockAdapter, $this->table->adapter);
    }

    #[Test]
    public function magicGetReturnsTheLastInsertValue(): void
    {
        static::assertNull($this->table->lastInsertValue);
    }

    #[Test]
    public function magicGetReturnsTheLastInsertValueAndAdapter(): void
    {
        $this->table->insert(['foo']); // trigger last insert id update

        static::assertSame(10, $this->table->lastInsertValue);
        static::assertSame($this->mockAdapter, $this->table->adapter);

        //self::assertEquals('foo', $this->table->table);
    }

    #[Test]
    public function magicGetReturnsTheTable(): void
    {
        static::assertSame('foo', $this->table->table);
    }

    #[Test]
    public function magicGetThrowsForUnknownProperty(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(
            sprintf(InvalidArgumentException::INVALID_MAGIC_GET, $this->table::class),
        );

        /** @phpstan-ignore expr.resultUnused, property.notFound */
        $this->table->invalidProperty;
    }

    #[Test]
    public function magicSetDelegatesToTheFeatureSet(): void
    {
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

    #[Test]
    public function magicSetThrowsForUnknownProperty(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(
            sprintf(InvalidArgumentException::INVALID_MAGIC_SET, $this->table::class),
        );

        /** @phpstan-ignore property.notFound */
        $this->table->invalidProperty = 'value';
    }

    /**
     * @param list<mixed>               $arguments
     * @param list<array{string, int}>  $expectedEvents
     */
    #[Test]
    #[DataProvider('featureEventProvider')]
    public function operationsApplyTheirPreAndPostFeatureEvents(
        string $method,
        array $arguments,
        array $expectedEvents,
    ): void {
        $events     = [];
        $featureSet = $this->getMockBuilder(FeatureSet::class)->onlyMethods(['apply'])->getMock();
        $featureSet->method('apply')
            ->willReturnCallback(
                static function (string $event, array $args) use (&$events): void {
                    $events[] = [$event, count($args)];
                },
            );

        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        $tgReflection->getProperty('featureSet')->setValue($this->table, $featureSet);
        $tgReflection->getProperty('isInitialized')->setValue($this->table, true);

        $this->table->{$method}(...$arguments);

        static::assertSame($expectedEvents, $events);
    }

    /** @param list<mixed> $arguments */
    #[Test]
    #[DataProvider('initializingOperationProvider')]
    public function operationsInitializeTheGatewayFirst(string $method, array $arguments): void
    {
        $gateway      = $this->getMockBuilder(AbstractTableGateway::class)->onlyMethods([])->getMock();
        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        $tgReflection->getProperty('table')->setValue($gateway, 'foo');
        $tgReflection->getProperty('adapter')->setValue($gateway, $this->mockAdapter);

        $gateway->{$method}(...$arguments);

        static::assertTrue($gateway->isInitialized());
    }

    /** @param list<mixed> $arguments */
    #[Test]
    #[DataProvider('executeHookProvider')]
    public function operationsRunThroughTheOverridableExecuteHooks(string $method, array $arguments): void
    {
        $gateway = new HookRecordingTableGateway('foo', $this->mockAdapter);

        $gateway->{$method}(...$arguments);

        static::assertSame([$method], $gateway->executed);
    }

    #[Test]
    public function selectAppliesColumnsWhenStarSelected(): void
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
            ->willReturnMap([
                [Select::TABLE, 'foo'],
                [Select::COLUMNS, [Select::SQL_STAR]],
            ]);

        $select->expects($this->once())
            ->method('columns')
            ->with(['id', 'name', 'email']);

        $this->table->selectWith($select);
    }

    #[Test]
    public function selectInitializesTheResultSetWithTheStatementResult(): void
    {
        $resultSet = $this->table->select();

        static::assertInstanceOf(ResultSet::class, $resultSet);
        static::assertInstanceOf(ResultInterface::class, $resultSet->getDataSource());
    }

    #[Test]
    public function selectKeepsExplicitlySelectedColumns(): void
    {
        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        $tgReflection->getProperty('columns')->setValue($this->table, ['id', 'name', 'email']);

        $select = $this->getMockBuilder(Select::class)
            ->onlyMethods(['getRawState', 'columns'])
            ->setConstructorArgs(['foo'])
            ->getMock();
        $select->method('getRawState')
            ->willReturnMap([
                [Select::TABLE, 'foo'],
                [Select::COLUMNS, ['id']],
            ]);
        $select->expects($this->never())->method('columns');

        $this->table->selectWith($select);
    }

    #[Test]
    public function selectWithArrayTable(): void
    {
        // Case 1
        $select1 = $this->getMockBuilder(Select::class)->onlyMethods(['getRawState'])->getMock();
        $select1->expects($this->exactly(2))
            ->method('getRawState')
            ->willReturnMap([
                [Select::TABLE, 'foo'], // Standard table name format, valid according to Select::from()
                [Select::COLUMNS, []],
            ]);
        $return = $this->table->selectWith($select1);
        static::assertInstanceOf(ResultSet::class, $return);

        // Case 2
        $select1 = $this->getMockBuilder(Select::class)->onlyMethods(['getRawState'])->getMock();
        $select1->expects($this->exactly(2))
            ->method('getRawState')
            ->willReturnMap([
                [Select::TABLE, ['f' => 'foo']], // Alias table name format, valid according to Select::from()
                [Select::COLUMNS, []],
            ]);
        $return = $this->table->selectWith($select1);
        static::assertInstanceOf(ResultSet::class, $return);
    }

    #[Test]
    public function selectWithClosure(): void
    {
        $mockSelect = $this->mockSelect;
        $mockSelect->expects($this->any())
            ->method('getRawState')
            ->willReturnMap([
                [Select::TABLE, $this->table->getTable()],
                [Select::COLUMNS, []],
            ]);

        $mockSelect->expects($this->never())->method('where');

        $closureCalled = false;
        $result        = $this->table->select(static function ($select) use (&$closureCalled) {
            $closureCalled = true;
            self::assertInstanceOf(Select::class, $select);
        });

        static::assertTrue($closureCalled);
        static::assertInstanceOf(ResultSet::class, $result);
    }

    #[Test]
    public function selectWithNoWhere(): void
    {
        $resultSet = $this->table->select();

        // check return types
        static::assertInstanceOf(ResultSet::class, $resultSet);
        static::assertNotSame($this->table->getResultSetPrototype(), $resultSet);
    }

    #[Test]
    public function selectWithWhereString(): void
    {
        $mockSelect = $this->mockSelect;
        $mockSelect->expects($this->any())
            ->method('getRawState')
            ->willReturnMap([
                [Select::TABLE, $this->table->getTable()],
                [Select::COLUMNS, []],
            ]);

        // assert select::from() is called
        $mockSelect->expects($this->once())
            ->method('where')
            ->with(static::equalTo('foo'));

        $this->table->select('foo');
    }

    #[Test]
    #[DataProvider('uninitializedPropertyProvider')]
    public function throwsWhenAnInitializedDependencyIsUnset(string $property, string $getter): void
    {
        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        $tgReflection->getProperty('isInitialized')->setValue($this->table, true);
        $tgReflection->getProperty($property)->setValue($this->table, null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(RuntimeException::UNINITIALIZED_PROPERTY, $property));

        $this->table->{$getter}();
    }

    /** @param list<mixed> $arguments */
    #[Test]
    #[DataProvider('statementWithoutResultProvider')]
    public function throwsWhenTheStatementReturnsNoResult(string $method, array $arguments, string $statement): void
    {
        $nullStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $nullStatement->method('execute')->willReturn(null);

        $driver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $driver->method('createStatement')->willReturn($nullStatement);

        $platform = $this->getMockBuilder(PlatformInterface::class)->getMock();
        $platform->method('getSqlPlatformDecorator')->willReturn(new Sql\Platform\Platform($platform));

        $adapter = $this->getMockBuilder(Adapter::class)
            ->onlyMethods([])
            ->setConstructorArgs([$driver, $platform, $this->getMockBuilder(ResultSetInterface::class)->getMock()])
            ->getMock();

        $gateway      = $this->getMockBuilder(AbstractTableGateway::class)->onlyMethods([])->getMock();
        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        $tgReflection->getProperty('table')->setValue($gateway, 'foo');
        $tgReflection->getProperty('adapter')->setValue($gateway, $adapter);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(RuntimeException::MISSING_STATEMENT_RESULT, $statement));

        $gateway->{$method}(...$arguments);
    }

    #[Test]
    public function update(): void
    {
        $mockUpdate = $this->mockUpdate;

        // assert select::from() is called
        $mockUpdate->expects($this->once())
            ->method('where')
            ->with(static::equalTo('id = 2'));

        $affectedRows = $this->table->update(['foo' => 'bar'], 'id = 2');
        static::assertSame(5, $affectedRows);
    }

    #[Test]
    public function updateWith(): void
    {
        $update = $this->getMockBuilder(Update::class)
            ->onlyMethods(['getRawState'])
            ->setConstructorArgs(['foo'])
            ->getMock();

        $update->expects($this->any())
            ->method('getRawState')
            ->willReturnMap([['table', 'foo']]);

        $affectedRows = $this->table->updateWith($update);
        static::assertSame(5, $affectedRows);
    }

    #[Test]
    public function updateWithJoin(): void
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
            ->with(static::equalTo('id = 2'));

        $mockUpdate->expects($this->once())
            ->method('join')
            ->with($joins[0]['name'], $joins[0]['on'], $joins[0]['type']);

        $affectedRows = $this->table->update(['foo.field' => 'bar'], 'id = 2', $joins);
        static::assertSame(5, $affectedRows);
    }

    #[Test]
    public function updateWithJoinDefaultType(): void
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
            ->with(static::equalTo('id = 2'));

        $mockUpdate->expects($this->once())
            ->method('join')
            ->with($joins[0]['name'], $joins[0]['on'], Sql\Join::JOIN_INNER);

        $affectedRows = $this->table->update(['foo.field' => 'bar'], 'id = 2', $joins);
        static::assertSame(5, $affectedRows);
    }

    #[Test]
    public function updateWithNoCriteria(): void
    {
        /** @phpstan-ignore expr.resultUnused */
        $this->mockUpdate;

        $affectedRows = $this->table->update(['foo' => 'bar']);
        static::assertSame(5, $affectedRows);
    }

    #[Override]
    protected function setUp(): void
    {
        $mockResult = $this->getMockBuilder(ResultInterface::class)->getMock();
        $mockResult->expects($this->any())->method('getAffectedRows')->willReturn(5);

        $mockPlatform = $this->getMockBuilder(PlatformInterface::class)->getMock();
        $mockPlatform->expects($this->any())->method('getName')->willReturn('sql92');
        $mockPlatform->expects($this->any())
            ->method('getSqlPlatformDecorator')
            ->willReturn(new Sql\Platform\Platform($mockPlatform));

        $mockResultSet = $this->getMockBuilder(ResultSetInterface::class)->getMock();

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $mockStatement->expects($this->any())->method('execute')->willReturn($mockResult);

        $mockConnection = $this->getMockBuilder(ConnectionInterface::class)->getMock();
        $mockConnection->expects($this->any())->method('getLastGeneratedValue')->willReturn(10);

        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('createStatement')->willReturn($mockStatement);
        $mockDriver->expects($this->any())->method('getConnection')->willReturn($mockConnection);

        $this->mockSelect = $this->getMockBuilder(Select::class)
            ->onlyMethods(['where', 'getRawState'])
            ->setConstructorArgs(['foo'])
            ->getMock();

        $this->mockInsert = $this->getMockBuilder(Insert::class)
            ->onlyMethods(['prepareStatement', 'values'])
            ->setConstructorArgs(['foo'])
            ->getMock();

        $this->mockUpdate = $this->getMockBuilder(Update::class)
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
        $this->mockSql = $this->getMockBuilder(Sql\Sql::class)
            ->onlyMethods(['select', 'insert', 'update', 'delete'])
            ->setConstructorArgs([$this->mockAdapter, 'foo'])
            ->getMock();
        $this->mockSql->expects($this->any())->method('select')->willReturn($this->mockSelect);
        $this->mockSql->expects($this->any())->method('insert')->willReturn($this->mockInsert);
        $this->mockSql->expects($this->any())->method('update')->willReturn($this->mockUpdate);
        $this->mockSql->expects($this->any())->method('delete')->willReturn($this->mockDelete);

        $this->mockFeatureSet = $this->getMockBuilder(FeatureSet::class)->getMock();

        $this->table = $this->getMockBuilder(AbstractTableGateway::class)
            ->onlyMethods([])
            ->getMock();

        $tgReflection = new ReflectionClass(AbstractTableGateway::class);
        foreach ($tgReflection->getProperties() as $tgPropReflection) {
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
        }
    }
}
