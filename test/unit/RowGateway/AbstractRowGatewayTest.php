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
use PhpDb\RowGateway\Exception\InvalidArgumentException;
use PhpDb\RowGateway\Exception\RuntimeException;
use PhpDb\RowGateway\Feature\FeatureSet;
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
        $mockResult = $this->getMockBuilder(ResultInterface::class)->getMock();
        $mockResult->expects($this->any())->method('getAffectedRows')->willReturn(1);
        $this->mockResult = $mockResult;
        $mockStatement    = $this->getMockBuilder(StatementInterface::class)->getMock();
        $mockStatement->expects($this->any())->method('execute')->willReturn($mockResult);
        $mockConnection = $this->getMockBuilder(ConnectionInterface::class)->getMock();
        $mockDriver     = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('createStatement')->willReturn($mockStatement);
        $mockDriver->expects($this->any())->method('getConnection')->willReturn($mockConnection);

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
        $this->rowGateway['testColumn'] = 'test';
        self::assertEquals('test', $this->rowGateway->testColumn);
        self::assertEquals('test', $this->rowGateway['testColumn']);
    }

    // @codingStandardsIgnoreStart
    public function test__set(): void
    {
        // @codingStandardsIgnoreEnd
        $this->rowGateway->testColumn = 'test';
        self::assertEquals('test', $this->rowGateway->testColumn);
        self::assertEquals('test', $this->rowGateway['testColumn']);
    }

    // @codingStandardsIgnoreStart
    public function test__isset(): void
    {
        // @codingStandardsIgnoreEnd
        self::assertFalse(isset($this->rowGateway->foo));
        $this->rowGateway->foo = 'bar';
        self::assertTrue(isset($this->rowGateway->foo));
    }

    public function testOffsetExists(): void
    {
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
        $this->rowGateway['testColumn'] = 'test';
        self::assertEquals('test', $this->rowGateway->testColumn);
        self::assertEquals('test', $this->rowGateway['testColumn']);
    }

    // @codingStandardsIgnoreStart
    public function test__get(): void
    {
        // @codingStandardsIgnoreEnd
        $this->rowGateway->testColumn = 'test';
        self::assertEquals('test', $this->rowGateway->testColumn);
        self::assertEquals('test', $this->rowGateway['testColumn']);
    }

    public function testSaveInsert(): void
    {
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

        $this->mockResult->expects($this->any())->method('current')
            ->willReturn(['one' => 'foo', 'two' => 'bar']);

        // @todo Need to assert that $where was filled in

        $refRowGateway     = new ReflectionObject($this->rowGateway);
        $refRowGatewayProp = $refRowGateway->getProperty('primaryKeyData');

        $this->rowGateway->populate(['one' => 'foo', 'two' => 'bar']);

        self::assertNull($refRowGatewayProp->getValue($this->rowGateway));

        $this->rowGateway->save();

        self::assertEquals(['one' => 'foo', 'two' => 'bar'], $refRowGatewayProp->getValue($this->rowGateway));
    }

    public function testSaveUpdate(): void
    {
        $this->mockResult->expects($this->any())->method('current')
            ->willReturn(['id' => 6, 'name' => 'foo']);
        $this->rowGateway->populate(['id' => 6, 'name' => 'foo'], true);
        $this->rowGateway->save();
        self::assertEquals(6, $this->rowGateway['id']);
    }

    public function testSaveUpdateChangingPrimaryKey(): void
    {
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

        $this->mockResult->expects($this->any())
            ->method('current')
            ->willReturn(['id' => 7, 'name' => 'fooUpdated']);

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
        $this->rowGateway->populate(['id' => 5, 'name' => 'foo'], true);
        self::assertEquals(['id' => 5, 'name' => 'foo'], $this->rowGateway->toArray());

        $oldData = $this->rowGateway->exchangeArray(['id' => 10, 'name' => 'bar']);

        /** @phpstan-ignore staticMethod.alreadyNarrowedType */
        self::assertIsArray($oldData);
        self::assertEquals(['id' => 5, 'name' => 'foo'], $oldData);

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

    // @codingStandardsIgnoreStart
    public function test__getThrowsExceptionForInvalidColumn(): void
    {
        // @codingStandardsIgnoreEnd
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Not a valid column in this row');

        /** @phpstan-ignore property.notFound, expr.resultUnused */
        $this->rowGateway->nonExistentColumn;
    }

    public function testInitializeThrowsExceptionWhenTableIsNull(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This row object does not have a valid table set.');

        $rowGateway = new RowGateway('id', 'temp_table', $this->mockAdapter);

        $refRowGateway = new ReflectionObject($rowGateway);
        $tableProp     = $refRowGateway->getProperty('table');
        $tableProp->setValue($rowGateway, null);

        $isInitializedProp = $refRowGateway->getProperty('isInitialized');
        $isInitializedProp->setValue($rowGateway, false);

        $rowGateway->populate(['name' => 'test']);
    }

    public function testInitializeThrowsExceptionWhenPrimaryKeyColumnIsNull(): void
    {
        $rowGateway = $this->getMockBuilder(AbstractRowGateway::class)->onlyMethods([])->getMock();

        $refRowGateway = new ReflectionObject($rowGateway);

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
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This row object does not have a Sql object set.');

        $rowGateway = new RowGateway('id', 'temp_table', $this->mockAdapter);

        $refRowGateway = new ReflectionObject($rowGateway);

        $sqlProp = $refRowGateway->getProperty('sql');
        $sqlProp->setValue($rowGateway, null);

        $isInitializedProp = $refRowGateway->getProperty('isInitialized');
        $isInitializedProp->setValue($rowGateway, false);

        $rowGateway->populate(['name' => 'test']);
    }

    public function testInitializeOnlyRunsOnce(): void
    {
        $this->rowGateway->populate(['id' => 1, 'name' => 'foo'], true);

        $refRowGateway     = new ReflectionObject($this->rowGateway);
        $isInitializedProp = $refRowGateway->getProperty('isInitialized');
        self::assertTrue($isInitializedProp->getValue($this->rowGateway));

        $this->rowGateway->populate(['id' => 2, 'name' => 'bar'], true);

        self::assertEquals(2, $this->rowGateway['id']);
        self::assertEquals('bar', $this->rowGateway['name']);
    }

    public function testInitializeEarlyReturnWhenAlreadyInitialized(): void
    {
        $rowGateway = new RowGateway('id', 'test_table', $this->mockAdapter);

        $refRowGateway      = new ReflectionObject($rowGateway);
        $featureSetProp     = $refRowGateway->getProperty('featureSet');
        $originalFeatureSet = $featureSetProp->getValue($rowGateway);

        $isInitializedProp = $refRowGateway->getProperty('isInitialized');
        self::assertTrue($isInitializedProp->getValue($rowGateway));

        $rowGateway->populate(['id' => 2, 'name' => 'bar'], true);

        self::assertSame($originalFeatureSet, $featureSetProp->getValue($rowGateway));
    }

    public function testInitializeCreatesFeatureSetIfNotSet(): void
    {
        $rowGateway = $this->getMockBuilder(AbstractRowGateway::class)->onlyMethods([])->getMock();

        $refRowGateway = new ReflectionObject($rowGateway);

        $tableProp = $refRowGateway->getProperty('table');
        $tableProp->setValue($rowGateway, 'foo');

        $pkProp = $refRowGateway->getProperty('primaryKeyColumn');
        $pkProp->setValue($rowGateway, ['id']);

        $sqlProp = $refRowGateway->getProperty('sql');
        $sqlProp->setValue($rowGateway, new Sql($this->mockAdapter));

        $featureSetProp = $refRowGateway->getProperty('featureSet');
        self::assertNull($featureSetProp->getValue($rowGateway));

        $rowGateway->populate(['id' => 1, 'name' => 'test'], true);

        self::assertInstanceOf(FeatureSet::class, $featureSetProp->getValue($rowGateway));
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
