<?php

declare(strict_types=1);

namespace PhpDbTest\RowGateway;

use Override;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\RowGateway\AbstractRowGateway;
use PhpDb\RowGateway\Exception\RuntimeException;
use PhpDb\RowGateway\RowGateway;
use PhpDb\Sql\Select;
use PhpDb\Sql\Sql;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionObject;

#[IgnoreDeprecations]
#[CoversMethod(RowGateway::class, 'offsetSet')]
#[CoversMethod(RowGateway::class, '__set')]
#[CoversMethod(RowGateway::class, '__isset')]
#[CoversMethod(RowGateway::class, 'offsetExists')]
#[CoversMethod(RowGateway::class, '__unset')]
#[CoversMethod(RowGateway::class, 'offsetUnset')]
#[CoversMethod(RowGateway::class, 'offsetGet')]
#[CoversMethod(RowGateway::class, '__get')]
#[CoversMethod(RowGateway::class, 'save')]
#[CoversMethod(RowGateway::class, 'delete')]
#[CoversMethod(RowGateway::class, 'populate')]
#[CoversMethod(RowGateway::class, 'rowExistsInDatabase')]
#[CoversMethod(RowGateway::class, 'processPrimaryKeyData')]
#[CoversMethod(RowGateway::class, 'count')]
#[CoversMethod(RowGateway::class, 'toArray')]
#[CoversMethod(RowGateway::class, 'exchangeArray')]
final class AbstractRowGatewayTest extends TestCase
{
    /** @var Adapter&MockObject */
    protected Adapter|MockObject $mockAdapter;

    /** @var RowGateway */
    protected RowGateway|AbstractRowGateway|MockObject $rowGateway;

    /** @var ResultInterface&MockObject */
    protected ResultInterface|MockObject $mockResult;

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    #[Override]
    protected function setUp(): void
    {
        // mock the adapter, driver, and parts
        $mockResult = $this->getMockBuilder(ResultInterface::class)->getMock();
        $mockResult->expects($this->any())->method('getAffectedRows')->willReturn(1);
        $this->mockResult = $mockResult;
        $mockStatement    = $this->getMockBuilder(StatementInterface::class)->getMock();
        $mockStatement->expects($this->any())->method('execute')->willReturn($mockResult);
        $mockConnection = $this->getMockBuilder(ConnectionInterface::class)->getMock();
        $mockDriver     = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('createStatement')->willReturn($mockStatement);
        $mockDriver->expects($this->any())->method('getConnection')->willReturn($mockConnection);

        // setup mock adapter
        $this->mockAdapter = $this->getMockBuilder(Adapter::class)
            ->onlyMethods([])
            ->setConstructorArgs(
                [
                    $mockDriver,
                    $this->getMockBuilder(PlatformInterface::class)->getMock(),
                ]
            )->getMock();

        $this->rowGateway = $this->getMockBuilder(AbstractRowGateway::class)->onlyMethods([])->getMock();

        $rgPropertyValues = [
            'primaryKeyColumn' => ['id'],
            'table'            => 'foo',
            'sql'              => new Sql($this->mockAdapter),
        ];
        $this->setRowGatewayState($rgPropertyValues);
    }

    public function testOffsetSet(): void
    {
        // If we set with an index, both getters should retrieve the same value:
        $this->rowGateway['testColumn'] = 'test';
        self::assertEquals('test', $this->rowGateway->testColumn);
        self::assertEquals('test', $this->rowGateway['testColumn']);
    }

    // @codingStandardsIgnoreStart
    public function test__set(): void
    {
        // @codingStandardsIgnoreEnd
        // If we set with a property, both getters should retrieve the same value:
        $this->rowGateway->testColumn = 'test';
        self::assertEquals('test', $this->rowGateway->testColumn);
        self::assertEquals('test', $this->rowGateway['testColumn']);
    }

    // @codingStandardsIgnoreStart
    public function test__isset(): void
    {
        // @codingStandardsIgnoreEnd
        // Test isset before and after assigning to a property:
        self::assertFalse(isset($this->rowGateway->foo));
        $this->rowGateway->foo = 'bar';
        self::assertTrue(isset($this->rowGateway->foo));
    }

    public function testOffsetExists(): void
    {
        // Test isset before and after assigning to an index:
        self::assertFalse(isset($this->rowGateway['foo']));
        $this->rowGateway['foo'] = 'bar';
        self::assertTrue(isset($this->rowGateway['foo']));
    }

    // @codingStandardsIgnoreStart
    public function test__unset(): void
    {
        // @codingStandardsIgnoreEnd
        $this->rowGateway->foo = 'bar';
        self::assertEquals('bar', $this->rowGateway->foo);
        unset($this->rowGateway->foo);
        self::assertEmpty($this->rowGateway->foo);
        self::assertEmpty($this->rowGateway['foo']);
    }

    public function testOffsetUnset(): void
    {
        $this->rowGateway['foo'] = 'bar';
        self::assertEquals('bar', $this->rowGateway['foo']);
        unset($this->rowGateway['foo']);
        self::assertEmpty($this->rowGateway->foo);
        self::assertEmpty($this->rowGateway['foo']);
    }

    public function testOffsetGet(): void
    {
        // If we set with an index, both getters should retrieve the same value:
        $this->rowGateway['testColumn'] = 'test';
        self::assertEquals('test', $this->rowGateway->testColumn);
        self::assertEquals('test', $this->rowGateway['testColumn']);
    }

    // @codingStandardsIgnoreStart
    public function test__get(): void
    {
        // @codingStandardsIgnoreEnd
        // If we set with a property, both getters should retrieve the same value:
        $this->rowGateway->testColumn = 'test';
        self::assertEquals('test', $this->rowGateway->testColumn);
        self::assertEquals('test', $this->rowGateway['testColumn']);
    }

    public function testSaveInsert(): void
    {
        // test insert
        $this->mockResult->expects($this->any())->method('current')
            ->willReturn(['id' => 5, 'name' => 'foo']);
        $this->mockResult->expects($this->any())->method('getGeneratedValue')->willReturn(5);
        $this->rowGateway->populate(['name' => 'foo']);
        $this->rowGateway->save();
        self::assertEquals(5, $this->rowGateway->id);
        self::assertEquals(5, $this->rowGateway['id']);
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    #[RequiresPhp('<= 8.6')]
    public function testSaveInsertMultiKey(): void
    {
        $this->rowGateway = $this->getMockBuilder(AbstractRowGateway::class)->onlyMethods([])->getMock();

        $mockSql = $this->getMockBuilder(Sql::class)
                    ->setConstructorArgs([$this->mockAdapter])
                    ->onlyMethods([])
                    ->getMock();

        $rgPropertyValues = [
            'primaryKeyColumn' => ['one', 'two'],
            'table'            => 'foo',
            'sql'              => $mockSql,
        ];
        $this->setRowGatewayState($rgPropertyValues);

        // test insert
        $this->mockResult->expects($this->any())->method('current')
            ->willReturn(['one' => 'foo', 'two' => 'bar']);

        // @todo Need to assert that $where was filled in

        $refRowGateway     = new ReflectionObject($this->rowGateway);
        $refRowGatewayProp = $refRowGateway->getProperty('primaryKeyData');
        /** @psalm-suppress UnusedMethodCall */
        $refRowGatewayProp->setAccessible(true);

        $this->rowGateway->populate(['one' => 'foo', 'two' => 'bar']);

        self::assertNull($refRowGatewayProp->getValue($this->rowGateway));

        // save should setup the primaryKeyData
        $this->rowGateway->save();

        self::assertEquals(['one' => 'foo', 'two' => 'bar'], $refRowGatewayProp->getValue($this->rowGateway));
    }

    public function testSaveUpdate(): void
    {
        // test update
        $this->mockResult->expects($this->any())->method('current')
            ->willReturn(['id' => 6, 'name' => 'foo']);
        $this->rowGateway->populate(['id' => 6, 'name' => 'foo'], true);
        $this->rowGateway->save();
        self::assertEquals(6, $this->rowGateway['id']);
    }

    public function testSaveUpdateChangingPrimaryKey(): void
    {
        // this mock is the select to be used to re-fresh the rowobject's data
        $selectMock = $this->getMockBuilder(Select::class)
            ->onlyMethods(['where'])
            ->getMock();
        $selectMock->expects($this->once())
            ->method('where')
            ->with($this->equalTo(['id' => 7]))
            ->willReturn($selectMock);

        $sqlMock = $this->getMockBuilder(Sql::class)
            ->onlyMethods(['select'])
            ->setConstructorArgs([$this->mockAdapter])
            ->getMock();
        $sqlMock->expects($this->any())
            ->method('select')
            ->willReturn($selectMock);

        $this->setRowGatewayState(['sql' => $sqlMock]);

        // original mock returning updated data
        $this->mockResult->expects($this->any())
            ->method('current')
            ->willReturn(['id' => 7, 'name' => 'fooUpdated']);

        // populate forces an update in save(), seeds with original data (from db)
        $this->rowGateway->populate(['id' => 6, 'name' => 'foo'], true);
        $this->rowGateway->id = 7;
        $this->rowGateway->save();
        self::assertEquals(['id' => 7, 'name' => 'fooUpdated'], $this->rowGateway->toArray());
    }

    public function testDelete(): void
    {
        $this->rowGateway->foo = 'bar';
        $affectedRows          = $this->rowGateway->delete();
        self::assertFalse($this->rowGateway->rowExistsInDatabase());
        self::assertEquals(1, $affectedRows);
    }

    public function testPopulate(): void
    {
        $this->rowGateway->populate(['id' => 5, 'name' => 'foo']);
        self::assertEquals(5, $this->rowGateway['id']);
        self::assertEquals('foo', $this->rowGateway['name']);
        self::assertFalse($this->rowGateway->rowExistsInDatabase());

        $this->rowGateway->populate(['id' => 5, 'name' => 'foo'], true);
        self::assertTrue($this->rowGateway->rowExistsInDatabase());
    }

    public function testProcessPrimaryKeyData(): void
    {
        $this->rowGateway->populate(['id' => 5, 'name' => 'foo'], true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('a known key id was not found');
        $this->rowGateway->populate(['boo' => 5, 'name' => 'foo'], true);
    }

    public function testCount(): void
    {
        $this->rowGateway->populate(['id' => 5, 'name' => 'foo'], true);
        self::assertEquals(2, $this->rowGateway->count());
    }

    public function testToArray(): void
    {
        $this->rowGateway->populate(['id' => 5, 'name' => 'foo'], true);
        self::assertEquals(['id' => 5, 'name' => 'foo'], $this->rowGateway->toArray());
    }

    public function testExchangeArray(): void
    {
        $result = $this->rowGateway->exchangeArray(['id' => 10, 'name' => 'bar']);

        self::assertSame($this->rowGateway, $result);
        self::assertEquals(10, $this->rowGateway['id']);
        self::assertEquals('bar', $this->rowGateway['name']);
        self::assertTrue($this->rowGateway->rowExistsInDatabase());
    }

    public function testRowExistsInDatabaseReturnsFalseWhenNew(): void
    {
        $this->rowGateway->populate(['name' => 'foo']);
        self::assertFalse($this->rowGateway->rowExistsInDatabase());
    }

    public function testRowExistsInDatabaseReturnsTrueAfterPopulateWithTrue(): void
    {
        $this->rowGateway->populate(['id' => 5, 'name' => 'foo'], true);
        self::assertTrue($this->rowGateway->rowExistsInDatabase());
    }

    public function test__getThrowsExceptionForInvalidColumn(): void
    {
        $this->expectException(\PhpDb\RowGateway\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Not a valid column in this row');

        // Access a column that doesn't exist
        $this->rowGateway->nonExistentColumn;
    }

    public function testInitializeThrowsExceptionWhenTableIsNull(): void
    {
        $rowGateway = $this->getMockBuilder(AbstractRowGateway::class)->onlyMethods([])->getMock();

        $refRowGateway = new ReflectionObject($rowGateway);

        // Set primaryKeyColumn and sql, but leave table as null
        $pkProp = $refRowGateway->getProperty('primaryKeyColumn');
        $pkProp->setValue($rowGateway, ['id']);

        $sqlProp = $refRowGateway->getProperty('sql');
        $sqlProp->setValue($rowGateway, new Sql($this->mockAdapter));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This row object does not have a valid table set.');

        $rowGateway->populate(['name' => 'test']);
    }

    public function testInitializeThrowsExceptionWhenPrimaryKeyColumnIsNull(): void
    {
        $rowGateway = $this->getMockBuilder(AbstractRowGateway::class)->onlyMethods([])->getMock();

        $refRowGateway = new ReflectionObject($rowGateway);

        // Set table and sql, but leave primaryKeyColumn as null
        $tableProp = $refRowGateway->getProperty('table');
        $tableProp->setValue($rowGateway, 'foo');

        $sqlProp = $refRowGateway->getProperty('sql');
        $sqlProp->setValue($rowGateway, new Sql($this->mockAdapter));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This row object does not have a primary key column set.');

        $rowGateway->populate(['name' => 'test']);
    }

    public function testInitializeThrowsExceptionWhenSqlIsNull(): void
    {
        $rowGateway = $this->getMockBuilder(AbstractRowGateway::class)->onlyMethods([])->getMock();

        $refRowGateway = new ReflectionObject($rowGateway);

        // Set table and primaryKeyColumn, but leave sql as null
        $tableProp = $refRowGateway->getProperty('table');
        $tableProp->setValue($rowGateway, 'foo');

        $pkProp = $refRowGateway->getProperty('primaryKeyColumn');
        $pkProp->setValue($rowGateway, ['id']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This row object does not have a Sql object set.');

        $rowGateway->populate(['name' => 'test']);
    }

    public function testInitializeOnlyRunsOnce(): void
    {
        // Call populate twice - initialize should only run the first time
        $this->rowGateway->populate(['id' => 1, 'name' => 'foo'], true);
        $this->rowGateway->populate(['id' => 2, 'name' => 'bar'], true);

        // If initialize ran twice, it would have caused issues
        // Just verify the second populate worked
        self::assertEquals(2, $this->rowGateway['id']);
        self::assertEquals('bar', $this->rowGateway['name']);
    }

    public function testInitializeCreatesFeatureSetIfNotSet(): void
    {
        $rowGateway = $this->getMockBuilder(AbstractRowGateway::class)->onlyMethods([])->getMock();

        $refRowGateway = new ReflectionObject($rowGateway);

        // Set required properties but not featureSet
        $tableProp = $refRowGateway->getProperty('table');
        $tableProp->setValue($rowGateway, 'foo');

        $pkProp = $refRowGateway->getProperty('primaryKeyColumn');
        $pkProp->setValue($rowGateway, ['id']);

        $sqlProp = $refRowGateway->getProperty('sql');
        $sqlProp->setValue($rowGateway, new Sql($this->mockAdapter));

        // Verify featureSet is null initially
        $featureSetProp = $refRowGateway->getProperty('featureSet');
        self::assertNull($featureSetProp->getValue($rowGateway));

        // Trigger initialization
        $rowGateway->populate(['id' => 1, 'name' => 'test'], true);

        // Verify featureSet was created
        self::assertInstanceOf(\PhpDb\RowGateway\Feature\FeatureSet::class, $featureSetProp->getValue($rowGateway));
    }

    /**
     * @throws ReflectionException
     */
    #[RequiresPhp('<= 8.6')]
    protected function setRowGatewayState(array $properties): void
    {
        $refRowGateway = new ReflectionObject($this->rowGateway);
        foreach ($properties as $rgPropertyName => $rgPropertyValue) {
            $refRowGatewayProp = $refRowGateway->getProperty($rgPropertyName);
            $refRowGatewayProp->setValue($this->rowGateway, $rgPropertyValue);
        }
    }
}
