<?php

declare(strict_types=1);

namespace PhpDbTest\ResultSet;

use ArrayIterator;
use Exception;
use Override;
use PDOStatement;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\ResultSet\AbstractResultSet;
use PhpDb\ResultSet\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TypeError;

use function assert;

#[CoversMethod(AbstractResultSet::class, 'initialize')]
#[CoversMethod(AbstractResultSet::class, 'buffer')]
#[CoversMethod(AbstractResultSet::class, 'isBuffered')]
#[CoversMethod(AbstractResultSet::class, 'getDataSource')]
#[CoversMethod(AbstractResultSet::class, 'getFieldCount')]
#[CoversMethod(AbstractResultSet::class, 'next')]
#[CoversMethod(AbstractResultSet::class, 'key')]
#[CoversMethod(AbstractResultSet::class, 'current')]
#[CoversMethod(AbstractResultSet::class, 'valid')]
#[CoversMethod(AbstractResultSet::class, 'rewind')]
#[CoversMethod(AbstractResultSet::class, 'count')]
#[CoversMethod(AbstractResultSet::class, 'toArray')]
final class AbstractResultSetTest extends TestCase
{
    protected MockObject|AbstractResultSet $resultSet;

    private function createResultSetMock(): MockObject|AbstractResultSet
    {
        return $this->getMockBuilder(AbstractResultSet::class)
            ->onlyMethods(['setRowPrototype', 'getRowPrototype'])
            ->getMock();
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->resultSet = $this->createResultSetMock();
    }

    /**
     * @throws Exception
     */
    public function testInitialize(): void
    {
        $resultSet = $this->createResultSetMock();

        // Verify initialize() accepts array data and returns fluent interface
        self::assertSame($resultSet, $resultSet->initialize([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));

        // Verify invalid data type throws exception
        $this->expectException(TypeError::class);
        /** @noinspection ALL */
        $resultSet->initialize('foo');
    }

    /**
     * @throws Exception
     */
    public function testInitializeDoesNotCallCount(): void
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
    public function testInitializeWithEmptyArray(): void
    {
        $resultSet = $this->createResultSetMock();
        // Verify initialize() accepts empty array
        self::assertSame($resultSet, $resultSet->initialize([]));
    }

    /**
     * @throws Exception
     */
    public function testBuffer(): void
    {
        $resultSet = $this->createResultSetMock();
        // Verify buffer() returns fluent interface
        self::assertSame($resultSet, $resultSet->buffer());

        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));
        $resultSet->next(); // start iterator
        // Verify buffer() throws exception when called after iteration starts
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Buffering must be enabled before iteration is started');
        $resultSet->buffer();
    }

    public function testIsBuffered(): void
    {
        $resultSet = $this->createResultSetMock();
        // Verify buffering is disabled by default
        self::assertFalse($resultSet->isBuffered());
        $resultSet->buffer();
        // Verify buffering is enabled after buffer() call
        self::assertTrue($resultSet->isBuffered());
    }

    /**
     * @throws Exception
     */
    public function testGetDataSource(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));
        // Verify getDataSource() returns the initialized iterator
        self::assertInstanceOf(ArrayIterator::class, $resultSet->getDataSource());
    }

    /**
     * @throws Exception
     */
    public function testGetFieldCount(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
        ]));
        // Verify getFieldCount() returns number of columns in current row
        self::assertEquals(2, $resultSet->getFieldCount());
    }

    /**
     * @throws Exception
     */
    public function testNext(): void
    {
        $rows = [
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ];

        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator($rows));

        // Verify next() advances iterator position
        self::assertSame(0, $resultSet->key());
        $resultSet->next();
        self::assertSame(1, $resultSet->key());
    }

    /**
     * @throws Exception
     */
    public function testKey(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));
        // Verify key() returns current iterator position
        $resultSet->next();
        self::assertEquals(1, $resultSet->key());
        $resultSet->next();
        self::assertEquals(2, $resultSet->key());
        $resultSet->next();
        self::assertEquals(3, $resultSet->key());
    }

    /**
     * @throws Exception
     */
    public function testCurrent(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));
        // Verify current() returns the current row
        self::assertEquals(['id' => 1, 'name' => 'one'], $resultSet->current());
    }

    /**
     * @throws Exception
     */
    public function testValid(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));
        // Verify valid() returns true when iterator is at valid position
        self::assertTrue($resultSet->valid());
        $resultSet->next();
        $resultSet->next();
        $resultSet->next();
        // Verify valid() returns false after iterating past last element
        self::assertFalse($resultSet->valid());
    }

    /**
     * @throws Exception
     */
    public function testRewindResetsIteratorPosition(): void
    {
            $rows = [
                ['id' => 1, 'name' => 'one'],
                ['id' => 2, 'name' => 'two'],
                ['id' => 3, 'name' => 'three'],
            ];

            $this->resultSet->initialize(new ArrayIterator($rows));

            // Move forward to ensure position changes
            $this->resultSet->next();
            self::assertSame(1, $this->resultSet->key());

            // Verify rewind() resets iterator position and current row
            $this->resultSet->rewind();
            self::assertSame(0, $this->resultSet->key());
            self::assertEquals($rows[0], $this->resultSet->current());
    }

    /**
     * @throws Exception
     */
    public function testCount(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));
        // Verify count() returns total number of rows
        self::assertEquals(3, $resultSet->count());
    }

    /**
     * @throws Exception
     */
    public function testToArray(): void
    {
        $resultSet = $this->createResultSetMock();
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));
        // Verify toArray() returns all rows as array
        self::assertEquals(
            [
                ['id' => 1, 'name' => 'one'],
                ['id' => 2, 'name' => 'two'],
                ['id' => 3, 'name' => 'three'],
            ],
            $resultSet->toArray()
        );
    }

    /**
     * Test multiple iterations with buffer
     *
     * @throws Exception
     */
    #[Group('issue-6845')]
    public function testBufferIterations(): void
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
        self::assertEquals(1, $data['id']);
        $resultSet->next();
        $data = $resultSet->current();
        self::assertEquals(2, $data['id']);

        // Rewind and iterate again to verify buffering allows rewind
        $resultSet->rewind();
        $data = $resultSet->current();
        self::assertEquals(1, $data['id']);
        $resultSet->next();
        $data = $resultSet->current();
        self::assertEquals(2, $data['id']);
        $resultSet->next();
        $data = $resultSet->current();
        self::assertEquals(3, $data['id']);
    }

    /**
     * Test multiple iterations with buffer with multiple rewind() calls
     *
     * @throws Exception
     */
    #[Group('issue-6845')]
    public function testMultipleRewindBufferIterations(): void
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
            ->willReturnCallback(function () use ($data) {
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
        self::assertEquals(1, $data['id']);
        $resultSet->next();
        $data = $resultSet->current();
        self::assertEquals(2, $data['id']);

        // Rewind multiple times and iterate again to verify buffering handles multiple rewinds
        $resultSet->rewind();
        $resultSet->rewind();

        $data = $resultSet->current();
        self::assertEquals(1, $data['id']);
        $resultSet->next();
        $data = $resultSet->current();
        self::assertEquals(2, $data['id']);
        $resultSet->next();
        $data = $resultSet->current();
        self::assertEquals(3, $data['id']);
    }
}
