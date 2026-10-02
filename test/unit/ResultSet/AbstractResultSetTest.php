<?php

declare(strict_types=1);

namespace PhpDbTest\ResultSet;

use ArrayIterator;
use ArrayObject;
use Exception;
use IteratorAggregate;
use NoRewindIterator;
use Override;
use PDOStatement;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\ResultSet\AbstractResultSet;
use PhpDb\ResultSet\Exception\RuntimeException;
use PhpDbTest\ResultSet\TestAsset\PassThroughResultSet;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TypeError;

use function assert;
use function iterator_to_array;

#[CoversMethod(AbstractResultSet::class, 'initialize')]
#[CoversMethod(AbstractResultSet::class, 'resolveIterator')]
#[CoversMethod(AbstractResultSet::class, 'dataSource')]
#[CoversMethod(AbstractResultSet::class, 'buffer')]
#[CoversMethod(AbstractResultSet::class, 'isBuffered')]
#[CoversMethod(AbstractResultSet::class, 'getDataSource')]
#[CoversMethod(AbstractResultSet::class, 'getFieldCount')]
#[CoversMethod(AbstractResultSet::class, 'next')]
#[CoversMethod(AbstractResultSet::class, 'key')]
#[CoversMethod(AbstractResultSet::class, 'currentRow')]
#[CoversMethod(AbstractResultSet::class, 'initializeFromResult')]
#[CoversMethod(AbstractResultSet::class, 'setBufferState')]
#[CoversMethod(AbstractResultSet::class, 'valid')]
#[CoversMethod(AbstractResultSet::class, 'rewind')]
#[CoversMethod(AbstractResultSet::class, 'count')]
final class AbstractResultSetTest extends TestCase
{
    protected AbstractResultSet $resultSet;

    /**
     * @throws Exception
     */
    #[Test]
    public function buffer(): void
    {
        $resultSet = $this->createResultSetMock();
        // Verify buffer() returns fluent interface
        static::assertSame($resultSet, $resultSet->buffer());

        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));
        $resultSet->next(); // start iterator
        // Verify buffer() throws exception when called after iteration starts
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(RuntimeException::UNBUFFERED_ITERATION);
        $resultSet->buffer();
    }

    #[Test]
    public function bufferAfterABufferedPassHasBegunKeepsBuffering(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([['id' => 1], ['id' => 2]]));
        $resultSet->buffer();
        $resultSet->current();
        $resultSet->next();

        $resultSet->buffer();
        $resultSet->rewind();

        static::assertSame(['id' => 1], $resultSet->current());
    }

    #[Test]
    public function bufferAfterIteratingAnArrayDataSourceIsAllowed(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize([['id' => 1], ['id' => 2]]);
        $resultSet->current();
        $resultSet->next();

        $resultSet->buffer();

        static::assertTrue($resultSet->isBuffered());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function bufferBeforeInitializeHoldsTheRowsOfTheDataSourceGivenLater(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->buffer();
        $resultSet->initialize(new NoRewindIterator(new ArrayIterator([['id' => 1], ['id' => 2]])));
        iterator_to_array($resultSet);

        static::assertSame([['id' => 1], ['id' => 2]], iterator_to_array($resultSet));
    }

    #[Test]
    public function bufferCalledTwiceKeepsTheRowsAlreadyHeld(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([['id' => 1]]));
        $resultSet->buffer();
        $resultSet->current();

        $resultSet->buffer();
        $resultSet->rewind();

        static::assertSame(['id' => 1], $resultSet->current());
    }

    /**
     * Test multiple iterations with buffer
     *
     * @throws Exception
     */
    #[Test]
    #[Group('issue-6845')]
    public function bufferIterations(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));
        $resultSet->buffer();

        // Iterate through rows and verify data
        $data = $resultSet->current();
        static::assertSame(1, $data['id']);
        $resultSet->next();
        $data = $resultSet->current();
        static::assertSame(2, $data['id']);

        // Rewind and iterate again to verify buffering allows rewind
        $resultSet->rewind();
        $data = $resultSet->current();
        static::assertSame(1, $data['id']);
        $resultSet->next();
        $data = $resultSet->current();
        static::assertSame(2, $data['id']);
        $resultSet->next();
        $data = $resultSet->current();
        static::assertSame(3, $data['id']);
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    /**
     * A table gateway clones its result set prototype for every select, so two result
     * sets cloned from one prototype must not hand each other's rows back.
     *
     * @throws Exception
     */
    #[Test]
    public function cloningAResultSetDoesNotShareTheRowsItHolds(): void
    {
        $prototype = new PassThroughResultSet();
        $prototype->initialize(new ArrayIterator([['id' => 1]]));
        $prototype->buffer();
        $prototype->current();

        $clone = clone $prototype;
        $clone->initialize(new ArrayIterator([['id' => 2]]));

        static::assertSame(['id' => 2], $clone->current());
        static::assertSame(['id' => 1], $prototype->current());
    }

    #[Test]
    public function countReturnsCachedResult(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize([['id' => 1], ['id' => 2]]);

        $first  = $resultSet->count();
        $second = $resultSet->count();

        static::assertSame(2, $first);
        static::assertSame($first, $second);
    }

    #[Test]
    public function countReturnsNullForUncountableDataSource(): void
    {
        $resultSet = $this->createResultSetMock();
        $iterator  = new NoRewindIterator(new ArrayIterator([['id' => 1]]));
        $resultSet->initialize($iterator);

        static::assertNull($resultSet->count());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function countsRowsInDataSource(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));
        // Verify count() returns total number of rows
        static::assertSame(3, $resultSet->count());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function current(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));
        // Verify current() returns the current row
        static::assertEquals(['id' => 1, 'name' => 'one'], $resultSet->current());
    }

    /**
     * The mysqli Result closes its statement once a fetch finds no row, so a valid()
     * asked after that fetches again from a closed statement.
     *
     * @throws Exception
     */
    #[Test]
    public function currentDoesNotAskAnExhaustedDriverResultWhetherItIsValid(): void
    {
        $result = $this->createMock(ResultInterface::class);
        $result->method('current')->willReturn(null);
        $result->expects(self::never())->method('valid');

        $resultSet = $this->createResultSetMock();
        $resultSet->initialize($result);

        static::assertNull($resultSet->current());
    }

    #[Test]
    public function currentReportsAnUninitialisedDataSourceRatherThanFailingOnNull(): void
    {
        $resultSet = $this->createResultSetMock();

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(RuntimeException::UNINITIALISED_DATA_SOURCE);

        $resultSet->current();
    }

    #[Test]
    public function currentReportsAnUninitialisedDataSourceWhileBuffering(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->buffer();

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(RuntimeException::UNINITIALISED_DATA_SOURCE);

        $resultSet->current();
    }

    #[Test]
    public function currentReturnsBufferedDataOnSecondPass(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
        ]));
        $resultSet->buffer();

        $firstPass = [];
        foreach ($resultSet as $row) {
            $firstPass[] = $row;
        }

        $resultSet->rewind();

        $secondPass = [];
        foreach ($resultSet as $row) {
            $secondPass[] = $row;
        }

        static::assertEquals($firstPass, $secondPass);
    }

    #[Test]
    public function currentReturnsNullForAFalseRowFromTheDataSource(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([false]));

        static::assertNull($resultSet->current());
    }

    #[Test]
    public function currentReturnsNullPastTheLastRowWhileBuffering(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([['id' => 1]]));
        $resultSet->buffer();
        $resultSet->current();
        $resultSet->next();

        static::assertNull($resultSet->current());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getDataSource(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));
        // Verify getDataSource() returns the initialized iterator
        static::assertInstanceOf(ArrayIterator::class, $resultSet->getDataSource());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getFieldCount(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
        ]));
        // Verify getFieldCount() returns number of columns in current row
        static::assertSame(2, $resultSet->getFieldCount());
    }

    #[Test]
    public function getFieldCountReturnsCachedValue(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize([['a' => 1, 'b' => 2]]);

        $first  = $resultSet->getFieldCount();
        $second = $resultSet->getFieldCount();

        static::assertSame(2, $first);
        static::assertSame($first, $second);
    }

    #[Test]
    public function getFieldCountReturnsZeroForEmptyIterator(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([]));

        static::assertSame(0, $resultSet->getFieldCount());
    }

    #[Test]
    public function getFieldCountReturnsZeroWithNoDataSource(): void
    {
        $resultSet = $this->createResultSetMock();

        static::assertSame(0, $resultSet->getFieldCount());
    }

    #[Test]
    public function getFieldCountWithCountableRow(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([new ArrayObject(['a' => 1, 'b' => 2, 'c' => 3])]));

        static::assertSame(3, $resultSet->getFieldCount());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function initialize(): void
    {
        $resultSet = $this->createResultSetMock();

        // Verify initialize() accepts array data and returns fluent interface
        static::assertSame($resultSet, $resultSet->initialize([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));

        // Verify invalid data type throws exception
        self::expectException(TypeError::class);
        /** @noinspection ALL */
        $resultSet->initialize('foo');
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function initializeDoesNotCallCount(): void
    {
        $resultSet = $this->createResultSetMock();
        $result    = $this->getMockBuilder(ResultInterface::class)->onlyMethods([])->getMock();
        $result->expects($this->never())->method('count');
        // Initialize with result and verify count() is never called
        $resultSet->initialize($result);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function initializeResetsBufferWhenAlreadyBuffered(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([['id' => 1]]));
        $resultSet->buffer();

        $resultSet->initialize(new ArrayIterator([['id' => 2]]));

        static::assertSame(2, $resultSet->current()['id']);
    }

    #[Test]
    public function initializeWithBufferedResultInterface(): void
    {
        $result = $this->createMock(ResultInterface::class);
        $result->method('isBuffered')->willReturn(true);
        $result->method('getFieldCount')->willReturn(2);

        $resultSet = $this->createResultSetMock();
        $resultSet->initialize($result);

        static::assertTrue($resultSet->isBuffered());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function initializeWithEmptyArray(): void
    {
        $resultSet = $this->createResultSetMock();
        // Verify initialize() accepts empty array
        static::assertSame($resultSet, $resultSet->initialize([]));
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function initializeWithIteratorAggregate(): void
    {
        $resultSet = $this->createResultSetMock();
        $aggregate = new class implements IteratorAggregate {
            public function getIterator(): ArrayIterator
            {
                return new ArrayIterator([['id' => 1], ['id' => 2]]);
            }
        };

        $resultSet->initialize($aggregate);

        static::assertSame(1, $resultSet->current()['id']);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function initializeWithResultInterfaceRewindsWhenBuffered(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([['id' => 1]]));
        $resultSet->buffer();

        $result = $this->createMock(ResultInterface::class);
        $result->method('getFieldCount')->willReturn(2);
        $result->method('isBuffered')->willReturn(false);
        $result->expects(self::once())->method('rewind');

        $resultSet->initialize($result);
    }

    #[Test]
    public function isBuffered(): void
    {
        $resultSet = $this->createResultSetMock();
        // Verify buffering is disabled by default
        static::assertFalse($resultSet->isBuffered());
        $resultSet->buffer();
        // Verify buffering is enabled after buffer() call
        static::assertTrue($resultSet->isBuffered());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function key(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));
        // Verify key() returns current iterator position
        $resultSet->next();
        static::assertSame(1, $resultSet->key());
        $resultSet->next();
        static::assertSame(2, $resultSet->key());
        $resultSet->next();
        static::assertSame(3, $resultSet->key());
    }

    /**
     * Test multiple iterations with buffer with multiple rewind() calls
     *
     * @throws Exception
     */
    #[Test]
    #[Group('issue-6845')]
    public function multipleRewindBufferIterations(): void
    {
        $resultSet = $this->createResultSetMock();
        $result    = new Result();
        $stub      = $this->getMockBuilder(PDOStatement::class)->getMock();
        $data      = new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]);
        assert($stub instanceof PDOStatement); // to suppress IDE type warnings
        $stub->expects($this->any())
            ->method('fetch')
            ->willReturnCallback(static function () use ($data) {
                $r = $data->current();
                $data->next();
                return $r;
            });
        $result->initialize($stub, null);
        $result->rewind();
        $result->rewind();

        $resultSet->initialize($result);
        $resultSet->buffer();
        $resultSet->rewind();
        $resultSet->rewind();

        // Iterate through rows
        $data = $resultSet->current();
        static::assertSame(1, $data['id']);
        $resultSet->next();
        $data = $resultSet->current();
        static::assertSame(2, $data['id']);

        // Rewind multiple times and iterate again to verify buffering handles multiple rewinds
        $resultSet->rewind();
        $resultSet->rewind();

        $data = $resultSet->current();
        static::assertSame(1, $data['id']);
        $resultSet->next();
        $data = $resultSet->current();
        static::assertSame(2, $data['id']);
        $resultSet->next();
        $data = $resultSet->current();
        static::assertSame(3, $data['id']);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function next(): void
    {
        $rows = [
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ];

        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator($rows));

        // Verify next() advances iterator position
        static::assertSame(0, $resultSet->key());
        $resultSet->next();
        static::assertSame(1, $resultSet->key());
    }

    #[Test]
    public function nextReportsAnUninitialisedDataSourceWhileBuffering(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->buffer();

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(RuntimeException::UNINITIALISED_DATA_SOURCE);

        $resultSet->next();
    }

    #[Test]
    public function rewindReportsAnUninitialisedDataSourceRatherThanFailingOnNull(): void
    {
        $resultSet = $this->createResultSetMock();

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(RuntimeException::UNINITIALISED_DATA_SOURCE);

        $resultSet->rewind();
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function rewindResetsIteratorPosition(): void
    {
        $rows = [
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ];

        $this->resultSet->initialize(new ArrayIterator($rows));

        // Move forward to ensure position changes
        $this->resultSet->next();
        static::assertSame(1, $this->resultSet->key());

        // Verify rewind() resets iterator position and current row
        $this->resultSet->rewind();
        static::assertSame(0, $this->resultSet->key());
        static::assertEquals($rows[0], $this->resultSet->current());
    }

    #[Test]
    public function rewindWithNonIteratorDataSource(): void
    {
        $resultSet = $this->createResultSetMock();
        $aggregate = new class implements IteratorAggregate {
            public function getIterator(): ArrayIterator
            {
                return new ArrayIterator([['id' => 1], ['id' => 2]]);
            }
        };

        $resultSet->initialize($aggregate);
        $resultSet->next();
        $resultSet->rewind();

        static::assertSame(0, $resultSet->key());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function valid(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));
        // Verify valid() returns true when iterator is at valid position
        static::assertTrue($resultSet->valid());
        $resultSet->next();
        $resultSet->next();
        $resultSet->next();
        // Verify valid() returns false after iterating past last element
        static::assertFalse($resultSet->valid());
    }

    /**
     * A buffered result set must answer from its buffer once the data source is spent,
     * rather than deferring to the exhausted source. That second pass is what buffering
     * exists for.
     *
     * @throws Exception
     */
    #[Test]
    public function validAnswersFromTheBufferAfterTheDataSourceIsExhausted(): void
    {
        $resultSet = $this->drainIntoBuffer();

        $resultSet->rewind();

        static::assertTrue($resultSet->valid());
    }

    #[Test]
    public function validReportsAnUninitialisedDataSourceRatherThanFailingOnNull(): void
    {
        $resultSet = $this->createResultSetMock();

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(RuntimeException::UNINITIALISED_DATA_SOURCE);

        $resultSet->valid();
    }

    #[Test]
    public function validReturnsFalseAfterLastElement(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1],
        ]));

        static::assertTrue($resultSet->valid());
        $resultSet->next();
        static::assertFalse($resultSet->valid());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function validReturnsFalseBeyondTheEndOfTheBuffer(): void
    {
        $resultSet = $this->drainIntoBuffer();

        // Left sitting one past the last buffered row, with nothing left in the source.
        static::assertFalse($resultSet->valid());
    }

    #[Test]
    public function validWithNonIteratorDataSource(): void
    {
        $resultSet = $this->createResultSetMock();
        $aggregate = new class implements IteratorAggregate {
            public function getIterator(): ArrayIterator
            {
                return new ArrayIterator([['id' => 1]]);
            }
        };

        $resultSet->initialize($aggregate);
        $resultSet->rewind();

        static::assertTrue($resultSet->valid());
    }

    #[Override]
    protected function setUp(): void
    {
        $this->resultSet = $this->createResultSetMock();
    }

    private function createResultSetMock(): AbstractResultSet
    {
        return new PassThroughResultSet();
    }

    /**
     * Walks a buffered result set to the end of its data source without iterating, so a
     * valid() that never goes false cannot spin here.
     *
     * @throws Exception
     */
    private function drainIntoBuffer(): AbstractResultSet
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
        ]));
        $resultSet->buffer();

        $resultSet->current();
        $resultSet->next();
        $resultSet->current();
        $resultSet->next();

        return $resultSet;
    }
}
