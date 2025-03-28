<?php

namespace LaminasTest\Db\ResultSet;

use ArrayIterator;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\ResultSet\AbstractResultSet;
use Laminas\Db\ResultSet\Exception\InvalidArgumentException;
use Laminas\Db\ResultSet\Exception\RuntimeException;
use PDOStatement;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
class AbstractResultSetTest extends TestCase
{
    /** @var MockObject */
    protected $resultSet;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->resultSet = $this->getMockForAbstractClass(AbstractResultSet::class);
    }

    public function testInitialize()
    {
        $resultSet = $this->getMockForAbstractClass(AbstractResultSet::class);

        self::assertSame($resultSet, $resultSet->initialize([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'DataSource provided is not an array, nor does it implement Iterator or IteratorAggregate'
        );
        $resultSet->initialize('foo');
    }

    public function testInitializeDoesNotCallCount()
    {
        $resultSet = $this->getMockForAbstractClass(AbstractResultSet::class);
        $result    = $this->getMockForAbstractClass(ResultInterface::class);
        $result->expects($this->never())->method('count');
        $resultSet->initialize($result);
    }

    public function testInitializeWithEmptyArray()
    {
        $resultSet = $this->getMockForAbstractClass(AbstractResultSet::class);
        self::assertSame($resultSet, $resultSet->initialize([]));
    }

    public function testBuffer()
    {
        $resultSet = $this->getMockForAbstractClass(AbstractResultSet::class);
        self::assertSame($resultSet, $resultSet->buffer());

        $resultSet = $this->getMockForAbstractClass(AbstractResultSet::class);
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));
        $resultSet->next(); // start iterator
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Buffering must be enabled before iteration is started');
        $resultSet->buffer();
    }

    public function testIsBuffered()
    {
        $resultSet = $this->getMockForAbstractClass(AbstractResultSet::class);
        self::assertFalse($resultSet->isBuffered());
        $resultSet->buffer();
        self::assertTrue($resultSet->isBuffered());
    }

    public function testGetDataSource()
    {
        $resultSet = $this->getMockForAbstractClass(AbstractResultSet::class);
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));
        self::assertInstanceOf(ArrayIterator::class, $resultSet->getDataSource());
    }

    public function testGetFieldCount()
    {
        $resultSet = $this->getMockForAbstractClass(AbstractResultSet::class);
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
        ]));
        self::assertEquals(2, $resultSet->getFieldCount());
    }

    public function testNext()
    {
        $resultSet = $this->getMockForAbstractClass(AbstractResultSet::class);
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));
        self::assertNull($resultSet->next());
    }

    public function testKey()
    {
        $resultSet = $this->getMockForAbstractClass(AbstractResultSet::class);
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));
        $resultSet->next();
        self::assertEquals(1, $resultSet->key());
        $resultSet->next();
        self::assertEquals(2, $resultSet->key());
        $resultSet->next();
        self::assertEquals(3, $resultSet->key());
    }

    public function testCurrent()
    {
        $resultSet = $this->getMockForAbstractClass(AbstractResultSet::class);
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));
        self::assertEquals(['id' => 1, 'name' => 'one'], $resultSet->current());
    }

    public function testValid()
    {
        $resultSet = $this->getMockForAbstractClass(AbstractResultSet::class);
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));
        self::assertTrue($resultSet->valid());
        $resultSet->next();
        $resultSet->next();
        $resultSet->next();
        self::assertFalse($resultSet->valid());
    }

    public function testRewind()
    {
        $resultSet = $this->getMockForAbstractClass(AbstractResultSet::class);
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));
        self::assertNull($resultSet->rewind());
    }

    public function testCount()
    {
        $resultSet = $this->getMockForAbstractClass(AbstractResultSet::class);
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));
        self::assertEquals(3, $resultSet->count());
    }

    public function testToArray()
    {
        $resultSet = $this->getMockForAbstractClass(AbstractResultSet::class);
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));
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
     */
    #[Group('issue-6845')]
    public function testBufferIterations()
    {
        $resultSet = $this->getMockForAbstractClass(AbstractResultSet::class);
        $resultSet->initialize(new ArrayIterator([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]));
        $resultSet->buffer();

        $data = $resultSet->current();
        self::assertEquals(1, $data['id']);
        $resultSet->next();
        $data = $resultSet->current();
        self::assertEquals(2, $data['id']);

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
     */
    #[Group('issue-6845')]
    public function testMultipleRewindBufferIterations()
    {
        $resultSet = $this->getMockForAbstractClass(AbstractResultSet::class);
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

        $data = $resultSet->current();
        self::assertEquals(1, $data['id']);
        $resultSet->next();
        $data = $resultSet->current();
        self::assertEquals(2, $data['id']);

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
