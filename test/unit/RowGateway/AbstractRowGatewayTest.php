<?php

namespace LaminasTest\Db\RowGateway;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\RowGateway\AbstractRowGateway;
use Laminas\Db\RowGateway\Exception\RuntimeException;
use Laminas\Db\RowGateway\RowGateway;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionObject;

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
            ->setConstructorArgs([$mockDriver])
            ->getMock();

        $this->rowGateway = $this->getMockForAbstractClass(AbstractRowGateway::class);

        $rgPropertyValues = [
            'primaryKeyColumn' => 'id',
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
    public function testSaveInsertMultiKey(): void
    {
        $this->rowGateway = $this->getMockForAbstractClass(AbstractRowGateway::class);

        $mockSql = $this->getMockForAbstractClass(Sql::class, [$this->mockAdapter]);

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

    /**
     * @throws ReflectionException
     */
    protected function setRowGatewayState(array $properties): void
    {
        $refRowGateway = new ReflectionObject($this->rowGateway);
        foreach ($properties as $rgPropertyName => $rgPropertyValue) {
            $refRowGatewayProp = $refRowGateway->getProperty($rgPropertyName);
            /** @psalm-suppress UnusedMethodCall */
            $refRowGatewayProp->setAccessible(true);
            $refRowGatewayProp->setValue($this->rowGateway, $rgPropertyValue);
        }
    }
}
