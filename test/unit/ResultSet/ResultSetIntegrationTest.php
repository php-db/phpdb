<?php

declare(strict_types=1);

namespace PhpDbTest\ResultSet;

use ArrayIterator;
use ArrayObject;
use Override;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\ResultSet\AbstractResultSet;
use PhpDb\ResultSet\Exception\InvalidArgumentException;
use PhpDb\ResultSet\Exception\RuntimeException;
use PhpDb\ResultSet\Exception\ValueError;
use PhpDb\ResultSet\ResultSet;
use PhpDb\ResultSet\ResultSetReturnType;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Random\RandomException;
use SplStack;
use stdClass;
use TypeError;

use function count;
use function get_debug_type;
use function is_array;
use function random_int;
use function var_export;

#[CoversMethod(AbstractResultSet::class, 'currentRow')]
#[CoversMethod(AbstractResultSet::class, 'resolveIterator')]
#[CoversMethod(AbstractResultSet::class, 'buffer')]
#[CoversMethod(ResultSet::class, 'current')]
#[CoversMethod(ResultSet::class, 'mapRow')]
#[CoversMethod(ResultSet::class, 'getReturnType')]
#[CoversMethod(ResultSet::class, '__construct')]
#[CoversMethod(ResultSet::class, 'getArrayObjectPrototype')]
#[CoversMethod(ResultSet::class, 'getRowPrototype')]
#[CoversMethod(ResultSet::class, 'setArrayObjectPrototype')]
#[CoversMethod(ResultSet::class, 'setRowPrototype')]
#[CoversMethod(ResultSet::class, 'toArray')]
#[Group('unit')]
final class ResultSetIntegrationTest extends TestCase
{
    protected ResultSet $resultSet;

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

    /** @psalm-return array<array-key, array{0: mixed}> */
    /**
     * Every case in ResultSetReturnType and the type a row arrives as, so a case
     * cannot be added without deciding what current() does with it.
     *
     * @return array<string, array{0: ResultSetReturnType, 1: string}>
     */
    public static function returnTypeProvider(): array
    {
        $cases = [
            'ArrayObject fills the row prototype' => [ResultSetReturnType::ArrayObject, ArrayObject::class],
            'Prototype fills the row prototype'   => [ResultSetReturnType::Prototype, ArrayObject::class],
            'Array hands back the raw row'        => [ResultSetReturnType::Array, 'array'],
        ];

        self::assertSame(
            count(ResultSetReturnType::cases()),
            count($cases),
            'every ResultSetReturnType case needs a row type asserted here',
        );

        return $cases;
    }

    #[Test]
    public function aChangeToARowInOneBufferedPassDoesNotReachTheNext(): void
    {
        $this->resultSet->initialize(new ArrayIterator([['id' => 1, 'name' => 'one']]));
        $this->resultSet->buffer();

        $row = $this->resultSet->current();
        static::assertInstanceOf(ArrayObject::class, $row);
        $row['name'] = 'ONE';
        $this->resultSet->rewind();

        static::assertSame(['id' => 1, 'name' => 'one'], $this->resultSet->current()?->getArrayCopy());
    }

    #[Test]
    public function aRowPrototypeSetAfterABufferedPassShapesTheNext(): void
    {
        $this->resultSet->initialize(new ArrayIterator([['id' => 1]]));
        $this->resultSet->buffer();
        $this->resultSet->current();

        $prototype = new class extends ArrayObject {};
        $this->resultSet->setRowPrototype($prototype);
        $this->resultSet->rewind();

        static::assertInstanceOf($prototype::class, $this->resultSet->current());
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Test]
    public function bufferCalledAfterIterationThrowsException(): void
    {
        $this->resultSet->initialize($this->createMock(ResultInterface::class));
        $this->resultSet->current();

        // Verify buffer() throws exception when called after iteration has started
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(RuntimeException::UNBUFFERED_ITERATION);
        $this->resultSet->buffer();
    }

    /**
     * @throws \Exception
     */
    #[Test]
    public function canProvideArrayAsDataSource(): void
    {
        $dataSource = [['foo']];
        // Initialize with array data source and verify current row
        $this->resultSet->initialize($dataSource);
        static::assertEquals($dataSource[0], (array) $this->resultSet->current());

        $returnType = new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);
        $dataSource = [$returnType];
        // Test with custom ArrayObject prototype
        $this->resultSet->setArrayObjectPrototype($returnType);
        $this->resultSet->initialize($dataSource);
        static::assertEquals($dataSource[0], $this->resultSet->current());
        static::assertContains($dataSource[0], $this->resultSet);
    }

    /**
     * @throws \Exception
     */
    #[Test]
    public function canProvideIteratorAggregateAsDataSource(): void
    {
        $iterator          = new ArrayIterator([['id' => 1, 'name' => 'one']]);
        $iteratorAggregate = $this->getMockBuilder('IteratorAggregate')
            ->onlyMethods(['getIterator'])
            ->getMock();
        $iteratorAggregate->expects($this->any())->method('getIterator')->willReturn($iterator);
        // Initialize with IteratorAggregate and verify the iterator it wraps is stored
        $this->resultSet->initialize($iteratorAggregate);
        static::assertSame($iterator, $this->resultSet->getDataSource());
    }

    /**
     * @throws \Exception
     */
    #[Test]
    public function canProvideIteratorAsDataSource(): void
    {
        $it = new SplStack();
        // Initialize with iterator and verify it is stored as data source
        $this->resultSet->initialize($it);
        static::assertSame($it, $this->resultSet->getDataSource());
    }

    /**
     * @throws RandomException
     * @throws \Exception
     */
    #[Test]
    public function countReturnsCountOfRows(): void
    {
        $count      = random_int(3, 75);
        $dataSource = $this->getArrayDataSource($count);
        // Verify count() returns correct number of rows
        $this->resultSet->initialize($dataSource);
        static::assertEquals($count, $this->resultSet->count());
    }

    #[Test]
    public function currentClonesRowPrototypeOnEachCall(): void
    {
        $resultSet = new ResultSet(ResultSetReturnType::ArrayObject);
        $resultSet->initialize([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
        ]);

        $first = $resultSet->current();
        $resultSet->next();
        $second = $resultSet->current();

        static::assertNotSame($first, $second);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Test]
    public function currentRejectsARowThatIsNotArrayData(): void
    {
        $mockResult = $this->createMock(ResultInterface::class);
        $mockResult->method('current')->willReturn('Not an Array');

        $this->resultSet->initialize($mockResult);
        $this->resultSet->buffer();

        self::expectException(ValueError::class);
        self::expectExceptionMessage('A row of type "string"');

        $this->resultSet->current();
    }

    #[Test]
    public function currentReturnsArrayObjectWhenReturnTypeIsArrayObject(): void
    {
        $resultSet = new ResultSet(ResultSetReturnType::ArrayObject);
        $resultSet->initialize([['id' => 1, 'name' => 'one']]);

        $current = $resultSet->current();

        static::assertInstanceOf(ArrayObject::class, $current);
        static::assertSame(1, $current['id']);
    }

    #[Test]
    public function currentReturnsArrayWhenReturnTypeIsArray(): void
    {
        $resultSet = new ResultSet(ResultSetReturnType::Array);
        $resultSet->initialize([['id' => 1, 'name' => 'one']]);

        $current = $resultSet->current();

        static::assertIsArray($current);
        static::assertSame(1, $current['id']);
    }

    /**
     * @throws \Exception
     */
    #[Test]
    public function currentWithBufferingCallsDataSourceCurrentOnce(): void
    {
        $mockResult = $this->getMockBuilder(ResultInterface::class)->getMock();
        $mockResult->method('valid')->willReturn(true);
        $mockResult->expects($this->once())->method('current')->willReturn(['foo' => 'bar']);

        $this->resultSet->initialize($mockResult);
        $this->resultSet->buffer();
        // Call current() twice and verify data source is only called once due to buffering
        $this->resultSet->current();

        // assertion above will fail if this calls datasource current
        $this->resultSet->current();
    }

    #[Test]
    public function dataSourceIsNullByDefault(): void
    {
        // Verify data source is null before initialization
        static::assertNull($this->resultSet->getDataSource());
    }

    #[Test]
    #[DataProvider('returnTypeProvider')]
    public function everyReturnTypeYieldsItsRowType(ResultSetReturnType $returnType, string $expected): void
    {
        $resultSet = new ResultSet($returnType);
        $resultSet->initialize([['id' => 1, 'name' => 'one']]);

        static::assertSame($expected, get_debug_type($resultSet->current()));
    }

    #[Test]
    public function fieldCountIsZeroWithNoDataSourcePresent(): void
    {
        // Verify field count is 0 when no data source is set
        static::assertSame(0, $this->resultSet->getFieldCount());
    }

    /**
     * @throws \Exception
     */
    #[Test]
    public function fieldCountRepresentsNumberOfFieldsInARowOfData(): void
    {
        $resultSet  = new ResultSet(ResultSet::TYPE_ARRAY);
        $dataSource = $this->getArrayDataSource(10);
        // Verify field count matches number of columns in row data
        $resultSet->initialize($dataSource);
        static::assertSame(2, $resultSet->getFieldCount());
    }

    public function getArrayDataSource(int $count): ArrayIterator
    {
        $array = [];
        for ($i = 0; $i < $count; $i++) {
            $array[] = [
                'id'    => $i,
                'title' => "title {$i}",
            ];
        }

        return new ArrayIterator($array);
    }

    #[Test]
    public function getArrayObjectPrototypeDelegatesToGetRowPrototype(): void
    {
        static::assertSame(
            $this->resultSet->getRowPrototype(),
            $this->resultSet->getArrayObjectPrototype(),
        );
    }

    #[Test]
    public function getReturnTypeReturnsArrayWhenSetToArray(): void
    {
        $resultSet = new ResultSet(ResultSetReturnType::Array);

        static::assertSame(ResultSetReturnType::Array, $resultSet->getReturnType());
    }

    /**
     * @throws \Exception
     */
    #[Test]
    #[DataProvider('invalidReturnTypes')]
    public function invalidDataSourceRaisesException(mixed $dataSource): void
    {
        if (is_array($dataSource)) {
            $this->expectNotToPerformAssertions();
            // this is valid
            return;
        }

        // Verify invalid data source throws TypeError
        self::expectException(TypeError::class);
        $this->resultSet->initialize($dataSource);
    }

    /**
     * @throws \Exception
     */
    #[Test]
    public function rejectsAnIteratorAggregateThatNeverResolvesToAnIterator(): void
    {
        $iteratorAggregate = $this->getMockBuilder('IteratorAggregate')
            ->onlyMethods(['getIterator'])
            ->getMock();
        $iteratorAggregate->expects($this->any())->method('getIterator')->willReturn($iteratorAggregate);

        self::expectException(InvalidArgumentException::class);
        $this->resultSet->initialize($iteratorAggregate);
    }

    #[Test]
    public function returnTypeIsObjectByDefault(): void
    {
        // Verify default return type is ArrayObject
        static::assertEquals(ResultSetReturnType::ArrayObject, $this->resultSet->getReturnType());
    }

    #[Test]
    public function rowObjectPrototypeIsMutable(): void
    {
        $row1 = new ArrayObject(['test1' => 'value1']);
        $row2 = new ArrayObject(['test2' => 'value2']);

        // First mutation
        $this->resultSet->setArrayObjectPrototype($row1);

        // Verify the first mutation occurred
        static::assertSame($row1, $this->resultSet->getArrayObjectPrototype());

        // Second mutation to verify mutability
        $this->resultSet->setArrayObjectPrototype($row2);

        // Verify the instance was actually mutated
        static::assertSame($row2, $this->resultSet->getArrayObjectPrototype());
        static::assertNotSame($row1, $this->resultSet->getArrayObjectPrototype());
    }

    #[Test]
    public function rowObjectPrototypeIsPopulatedByRowObjectByDefault(): void
    {
        // Verify default row object prototype is ArrayObject
        $row = $this->resultSet->getArrayObjectPrototype();
        static::assertInstanceOf('ArrayObject', $row);
    }

    #[Test]
    public function rowObjectPrototypeMayBePassedToConstructor(): void
    {
        $row = new ArrayObject();
        // Verify prototype can be passed to constructor
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT, $row);
        static::assertSame($row, $resultSet->getArrayObjectPrototype());
    }

    #[Test]
    #[DataProvider('invalidReturnTypes')]
    public function settingInvalidReturnTypeRaisesException(mixed $type): void
    {
        // Verify invalid return type throws TypeError
        self::expectException(TypeError::class);
        new ResultSet(ResultSet::TYPE_ARRAYOBJECT, $type);
    }

    /**
     * @throws RandomException
     * @throws \Exception
     */
    #[Test]
    public function toArrayCreatesArrayOfArraysRepresentingRows(): void
    {
        $count      = random_int(3, 75);
        $dataSource = $this->getArrayDataSource($count);
        // Verify toArray() returns array representation of all rows
        $this->resultSet->initialize($dataSource);
        $test = $this->resultSet->toArray();
        static::assertEquals($dataSource->getArrayCopy(), $test, var_export($test, true));
    }

    /**
     * @throws \Exception
     */
    #[Test]
    public function whenReturnTypeIsArrayThenIterationReturnsArrays(): void
    {
        $resultSet  = new ResultSet(ResultSet::TYPE_ARRAY);
        $dataSource = $this->getArrayDataSource(10);
        $resultSet->initialize($dataSource);
        // Iterate and verify each row is returned as array
        foreach ($resultSet as $index => $row) {
            static::assertEquals($dataSource[$index], $row);
        }
    }

    /**
     * @throws \Exception
     */
    #[Test]
    public function whenReturnTypeIsObjectThenIterationReturnsRowObjects(): void
    {
        $dataSource = $this->getArrayDataSource(10);
        $this->resultSet->initialize($dataSource);
        // Iterate and verify each row is returned as ArrayObject
        foreach ($this->resultSet as $index => $row) {
            static::assertInstanceOf('ArrayObject', $row);
            static::assertEquals($dataSource[$index], $row->getArrayCopy());
        }
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->resultSet = new ResultSet();
    }

    #[Test]
    public function aSubclassGetRowPrototypeDecidesTheRowClass(): void
    {
        $resultSet = new class extends ResultSet {
            #[Override]
            public function getRowPrototype(): ArrayObject
            {
                return new class ([], ArrayObject::ARRAY_AS_PROPS) extends ArrayObject {};
            }
        };
        $resultSet->initialize([['id' => 1]]);

        $row = $resultSet->current();

        static::assertInstanceOf(ArrayObject::class, $row);
        static::assertNotSame(ArrayObject::class, $row::class);
    }

    #[Test]
    public function aBufferedResultSetBuildsFreshRowsOnEveryPass(): void
    {
        $resultSet = new ResultSet();
        $resultSet->initialize(new ArrayIterator([['id' => 1], ['id' => 2]]));
        $resultSet->buffer();

        $first = $resultSet->current();
        $resultSet->rewind();

        static::assertNotSame($first, $resultSet->current());
        static::assertEquals($first, $resultSet->current());
    }
}
