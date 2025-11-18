<?php

namespace PhpDbTest\ResultSet;

use ArrayIterator;
use ArrayObject;
use Override;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\ResultSet\AbstractResultSet;
use PhpDb\ResultSet\Exception\RuntimeException;
use PhpDb\ResultSet\ResultSet;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Random\RandomException;
use SplStack;
use stdClass;
use TypeError;

use function is_array;
use function random_int;
use function var_export;

#[CoversMethod(AbstractResultSet::class, 'current')]
#[CoversMethod(AbstractResultSet::class, 'buffer')]
final class ResultSetIntegrationTest extends TestCase
{
    protected ResultSet $resultSet;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->resultSet = new ResultSet();
    }

    public function testRowObjectPrototypeIsPopulatedByRowObjectByDefault(): void
    {
        // Verify default row object prototype is ArrayObject
        $row = $this->resultSet->getArrayObjectPrototype();
        self::assertInstanceOf('ArrayObject', $row);
    }

    public function testRowObjectPrototypeIsMutable(): void
    {
        $row1 = new ArrayObject(['test1' => 'value1']);
        $row2 = new ArrayObject(['test2' => 'value2']);

        // First mutation
        $this->resultSet->setArrayObjectPrototype($row1);

        // Verify the first mutation occurred
        self::assertSame($row1, $this->resultSet->getArrayObjectPrototype());

        // Second mutation to verify mutability
        $this->resultSet->setArrayObjectPrototype($row2);

        // Verify the instance was actually mutated
        self::assertSame($row2, $this->resultSet->getArrayObjectPrototype());
        self::assertNotSame($row1, $this->resultSet->getArrayObjectPrototype());
    }

    public function testRowObjectPrototypeMayBePassedToConstructor(): void
    {
        $row = new ArrayObject();
        // Verify prototype can be passed to constructor
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT, $row);
        self::assertSame($row, $resultSet->getArrayObjectPrototype());
    }

    public function testReturnTypeIsObjectByDefault(): void
    {
        // Verify default return type is TYPE_ARRAYOBJECT
        self::assertEquals(ResultSet::TYPE_ARRAYOBJECT, $this->resultSet->getReturnType());
    }

    /** @psalm-return array<array-key, array{0: mixed}> */
    public static function invalidReturnTypes(): array
    {
        return [
            [1],
            [1.0],
            [true],
            ['string'],
            [['foo']],
            [new stdClass()],
        ];
    }

    #[DataProvider('invalidReturnTypes')]
    public function testSettingInvalidReturnTypeRaisesException(mixed $type): void
    {
        // Verify invalid return type throws TypeError
        $this->expectException(TypeError::class);
        new ResultSet(ResultSet::TYPE_ARRAYOBJECT, $type);
    }

    public function testDataSourceIsNullByDefault(): void
    {
        // Verify data source is null before initialization
        self::assertNull($this->resultSet->getDataSource());
    }

    /**
     * @throws \Exception
     */
    public function testCanProvideIteratorAsDataSource(): void
    {
        $it = new SplStack();
        // Initialize with iterator and verify it is stored as data source
        $this->resultSet->initialize($it);
        self::assertSame($it, $this->resultSet->getDataSource());
    }

    /**
     * @throws \Exception
     */
    public function testCanProvideArrayAsDataSource(): void
    {
        $dataSource = [['foo']];
        // Initialize with array data source and verify current row
        $this->resultSet->initialize($dataSource);
        $this->assertEquals($dataSource[0], (array) $this->resultSet->current());

        $returnType = new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);
        $dataSource = [$returnType];
        // Test with custom ArrayObject prototype
        $this->resultSet->setArrayObjectPrototype($returnType);
        $this->resultSet->initialize($dataSource);
        $this->assertEquals($dataSource[0], $this->resultSet->current());
        $this->assertContains($dataSource[0], $this->resultSet);
    }

    /**
     * @throws \Exception
     */
    public function testCanProvideIteratorAggregateAsDataSource(): void
    {
        $iteratorAggregate = $this->getMockBuilder('IteratorAggregate')
            ->onlyMethods(['getIterator'])
            ->getMock();
        $iteratorAggregate->expects($this->any())->method('getIterator')->willReturn($iteratorAggregate);
        // Initialize with IteratorAggregate and verify its iterator is used
        $this->resultSet->initialize($iteratorAggregate);
        self::assertSame($iteratorAggregate->getIterator(), $this->resultSet->getDataSource());
    }

    /**
     * @throws \Exception
     */
    #[DataProvider('invalidReturnTypes')]
    public function testInvalidDataSourceRaisesException(mixed $dataSource): void
    {
        if (is_array($dataSource)) {
            $this->expectNotToPerformAssertions();
            // this is valid
            return;
        }

        // Verify invalid data source throws TypeError
        $this->expectException(TypeError::class);
        $this->resultSet->initialize($dataSource);
    }

    public function testFieldCountIsZeroWithNoDataSourcePresent(): void
    {
        // Verify field count is 0 when no data source is set
        self::assertEquals(0, $this->resultSet->getFieldCount());
    }

    public function getArrayDataSource(int $count): ArrayIterator
    {
        $array = [];
        for ($i = 0; $i < $count; $i++) {
            $array[] = [
                'id'    => $i,
                'title' => 'title ' . $i,
            ];
        }

        return new ArrayIterator($array);
    }

    /**
     * @throws \Exception
     */
    public function testFieldCountRepresentsNumberOfFieldsInARowOfData(): void
    {
        $resultSet  = new ResultSet(ResultSet::TYPE_ARRAY);
        $dataSource = $this->getArrayDataSource(10);
        // Verify field count matches number of columns in row data
        $resultSet->initialize($dataSource);
        self::assertEquals(2, $resultSet->getFieldCount());
    }

    /**
     * @throws \Exception
     */
    public function testWhenReturnTypeIsArrayThenIterationReturnsArrays(): void
    {
        $resultSet  = new ResultSet(ResultSet::TYPE_ARRAY);
        $dataSource = $this->getArrayDataSource(10);
        $resultSet->initialize($dataSource);
        // Iterate and verify each row is returned as array
        foreach ($resultSet as $index => $row) {
            self::assertEquals($dataSource[$index], $row);
        }
    }

    /**
     * @throws \Exception
     */
    public function testWhenReturnTypeIsObjectThenIterationReturnsRowObjects(): void
    {
        $dataSource = $this->getArrayDataSource(10);
        $this->resultSet->initialize($dataSource);
        // Iterate and verify each row is returned as ArrayObject
        foreach ($this->resultSet as $index => $row) {
            self::assertInstanceOf('ArrayObject', $row);
            self::assertEquals($dataSource[$index], $row->getArrayCopy());
        }
    }

    /**
     * @throws RandomException
     * @throws \Exception
     */
    public function testCountReturnsCountOfRows(): void
    {
        $count      = random_int(3, 75);
        $dataSource = $this->getArrayDataSource($count);
        // Verify count() returns correct number of rows
        $this->resultSet->initialize($dataSource);
        self::assertEquals($count, $this->resultSet->count());
    }

    /**
     * @throws RandomException
     * @throws \Exception
     */
    public function testToArrayRaisesExceptionForRowsThatAreNotArraysOrArrayCastable(): void
    {
        $count      = random_int(3, 75);
        $dataSource = $this->getArrayDataSource($count);
        foreach ($dataSource as $index => $row) {
            $dataSource[$index] = (object) $row;
        }

        // Verify toArray() throws exception for non-array-castable objects
        $this->resultSet->initialize($dataSource);
        $this->expectException(RuntimeException::class);
        $this->resultSet->toArray();
    }

    /**
     * @throws RandomException
     * @throws \Exception
     */
    public function testToArrayCreatesArrayOfArraysRepresentingRows(): void
    {
        $count      = random_int(3, 75);
        $dataSource = $this->getArrayDataSource($count);
        // Verify toArray() returns array representation of all rows
        $this->resultSet->initialize($dataSource);
        $test = $this->resultSet->toArray();
        self::assertEquals($dataSource->getArrayCopy(), $test, var_export($test, true));
    }

    /**
     * @throws \Exception
     */
    public function testCurrentWithBufferingCallsDataSourceCurrentOnce(): void
    {
        $mockResult = $this->getMockBuilder(ResultInterface::class)->getMock();
        $mockResult->expects($this->once())->method('current')->willReturn(['foo' => 'bar']);

        $this->resultSet->initialize($mockResult);
        $this->resultSet->buffer();
        // Call current() twice and verify data source is only called once due to buffering
        $this->resultSet->current();

        // assertion above will fail if this calls datasource current
        $this->resultSet->current();
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testBufferCalledAfterIterationThrowsException(): void
    {
        $this->resultSet->initialize($this->createMock(ResultInterface::class));
        $this->resultSet->current();

        // Verify buffer() throws exception when called after iteration has started
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Buffering must be enabled before iteration is started');
        $this->resultSet->buffer();
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testCurrentReturnsNullForNonExistingValues(): void
    {
        $mockResult = $this->createMock(ResultInterface::class);
        $mockResult->expects($this->once())->method('current')->willReturn("Not an Array");

        $this->resultSet->initialize($mockResult);
        $this->resultSet->buffer();

        // Verify current() returns null when data source returns non-array value
        self::assertNull($this->resultSet->current());
    }
}
