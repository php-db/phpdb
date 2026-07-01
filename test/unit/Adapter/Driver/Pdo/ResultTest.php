<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver\Pdo;

use PDO;
use PDOStatement;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Exception\InvalidArgumentException;
use PhpDb\Adapter\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use stdClass;

use function assert;
use function uniqid;

#[CoversMethod(Result::class, 'current')]
#[CoversMethod(Result::class, 'count')]
#[CoversMethod(Result::class, 'initialize')]
#[CoversMethod(Result::class, 'isBuffered')]
#[CoversMethod(Result::class, 'getFetchMode')]
#[CoversMethod(Result::class, 'setStatementMode')]
#[CoversMethod(Result::class, 'getStatementMode')]
#[CoversMethod(Result::class, 'getResource')]
#[CoversMethod(Result::class, 'getFieldCount')]
#[CoversMethod(Result::class, 'isQueryResult')]
#[CoversMethod(Result::class, 'getAffectedRows')]
#[CoversMethod(Result::class, 'getGeneratedValue')]
#[CoversMethod(Result::class, 'rewind')]
#[CoversMethod(Result::class, 'next')]
#[CoversMethod(Result::class, 'key')]
#[CoversMethod(Result::class, 'buffer')]
#[CoversMethod(Result::class, 'setFetchMode')]
#[CoversMethod(Result::class, 'valid')]
#[Group('result-pdo')]
#[Group('unit')]
final class ResultTest extends TestCase
{
    /**
     * Tests current method returns same data on consecutive calls.
     */
    public function testCurrentReturnsSameDataOnConsecutiveCalls(): void
    {
        $stub = $this->getMockBuilder('PDOStatement')->getMock();
        $stub->expects($this->any())
            ->method('fetch')
            ->willReturnCallback(fn() => uniqid());

        $result = new Result();
        $result->initialize($stub, null);

        self::assertEquals($result->current(), $result->current());
    }

    public function testSetFetchModeThrowsOnInvalidMode(): void
    {
        $result = new Result();

        $this->expectException(InvalidArgumentException::class);
        $result->setFetchMode(13);
    }

    /**
     * Tests whether the fetch mode was set properly and
     */
    public function testFetchModeObjReturnsStdClass(): void
    {
        $stub = $this->getMockBuilder('PDOStatement')->getMock();
        $stub->expects($this->any())
            ->method('fetch')
            ->willReturnCallback(fn() => new stdClass());

        $result = new Result();
        $result->initialize($stub, null);
        $result->setFetchMode(PDO::FETCH_OBJ);

        self::assertEquals(5, $result->getFetchMode());
        self::assertInstanceOf('stdClass', $result->current());
    }

    /**
     * Tests whether the fetch mode has a broader range
     */
    public function testFetchModeAcceptsNamedMode(): void
    {
        $stub = $this->getMockBuilder('PDOStatement')->getMock();
        $stub->expects($this->any())
            ->method('fetch')
            ->willReturnCallback(fn() => new stdClass());
        $result = new Result();
        $result->initialize($stub, null);
        $result->setFetchMode(PDO::FETCH_NAMED);
        self::assertEquals(11, $result->getFetchMode());
        self::assertInstanceOf('stdClass', $result->current());
    }

    public function testRewindResetsIterationToStart(): void
    {
        $data     = [
            ['test' => 1],
            ['test' => 2],
        ];
        $position = 0;

        $stub = $this->getMockBuilder('PDOStatement')->getMock();
        assert($stub instanceof PDOStatement); // to suppress IDE type warnings
        $stub->expects($this->any())
            ->method('fetch')
            ->willReturnCallback(function () use ($data, &$position) {
                return $data[$position++];
            });
        $result = new Result();
        $result->initialize($stub, null);

        $result->rewind();
        $result->rewind();

        $this->assertEquals(0, $result->key());
        $this->assertEquals(1, $position);
        $this->assertEquals($data[0], $result->current());

        $result->next();
        $this->assertEquals(1, $result->key());
        $this->assertEquals(2, $position);
        $this->assertEquals($data[1], $result->current());
    }

    public function testCountWithNoRowCountFallsBackToStatementRowCount(): void
    {
        $stub = $this->getMockBuilder(PDOStatement::class)->getMock();
        $stub->expects($this->once())
            ->method('rowCount')
            ->willReturn(5);

        $result = new Result();
        $result->initialize($stub, null);

        self::assertSame(5, $result->count());
    }

    public function testCountWithClosureInvokesClosureAndReturnsValue(): void
    {
        $stub = $this->getMockBuilder(PDOStatement::class)->getMock();
        $stub->expects($this->never())->method('rowCount');

        $rowCount = static fn(): int => 42;

        $result = new Result();
        $result->initialize($stub, null, $rowCount);

        self::assertSame(42, $result->count());
    }

    public function testCountWithIntReturnsProvidedValue(): void
    {
        $stub = $this->getMockBuilder(PDOStatement::class)->getMock();
        $stub->expects($this->never())->method('rowCount');

        $result = new Result();
        $result->initialize($stub, null, 10);

        self::assertSame(10, $result->count());
    }

    public function testCountCachesResultFromClosure(): void
    {
        $callCount = 0;
        $rowCount  = static function () use (&$callCount): int {
            $callCount++;
            return 7;
        };

        $stub = $this->getMockBuilder(PDOStatement::class)->getMock();

        $result = new Result();
        $result->initialize($stub, null, $rowCount);

        $result->count();
        $result->count();

        self::assertSame(1, $callCount);
    }

    public function testCountCachesResultFromStatementRowCount(): void
    {
        $stub = $this->getMockBuilder(PDOStatement::class)->getMock();
        $stub->expects($this->once())->method('rowCount')->willReturn(3);

        $result = new Result();
        $result->initialize($stub, null);

        $result->count();
        $result->count();

        self::assertSame(3, $result->count());
    }

    public function testInitializeStoresResourceAndValues(): void
    {
        $stub   = $this->createMock(PDOStatement::class);
        $result = new Result();

        $result->initialize($stub, 42, 5);

        self::assertSame(42, $result->getGeneratedValue());
        self::assertSame(5, $result->count());
    }

    public function testIsBufferedReturnsFalse(): void
    {
        $result = new Result();

        self::assertFalse($result->isBuffered());
    }

    public function testGetFetchModeDefaultIsAssoc(): void
    {
        $result = new Result();

        self::assertSame(PDO::FETCH_ASSOC, $result->getFetchMode());
    }

    public function testSetStatementModeToScrollable(): void
    {
        $result = new Result();

        $result->setStatementMode(Result::STATEMENT_MODE_SCROLLABLE);

        self::assertSame(Result::STATEMENT_MODE_SCROLLABLE, $result->getStatementMode());
    }

    public function testSetStatementModeToForward(): void
    {
        $result = new Result();

        $result->setStatementMode(Result::STATEMENT_MODE_FORWARD);

        self::assertSame(Result::STATEMENT_MODE_FORWARD, $result->getStatementMode());
    }

    public function testSetStatementModeThrowsOnInvalidMode(): void
    {
        $result = new Result();

        $this->expectException(InvalidArgumentException::class);
        $result->setStatementMode('invalid');
    }

    public function testGetResourceReturnsPdoStatement(): void
    {
        $stub   = $this->createMock(PDOStatement::class);
        $result = new Result();
        $result->initialize($stub, null);

        self::assertSame($stub, $result->getResource());
    }

    public function testGetFieldCountDelegatesToColumnCount(): void
    {
        $stub = $this->createMock(PDOStatement::class);
        $stub->method('columnCount')->willReturn(3);

        $result = new Result();
        $result->initialize($stub, null);

        self::assertSame(3, $result->getFieldCount());
    }

    public function testIsQueryResultReturnsTrueWhenColumnsExist(): void
    {
        $stub = $this->createMock(PDOStatement::class);
        $stub->method('columnCount')->willReturn(3);

        $result = new Result();
        $result->initialize($stub, null);

        self::assertTrue($result->isQueryResult());
    }

    public function testIsQueryResultReturnsFalseWhenNoColumns(): void
    {
        $stub = $this->createMock(PDOStatement::class);
        $stub->method('columnCount')->willReturn(0);

        $result = new Result();
        $result->initialize($stub, null);

        self::assertFalse($result->isQueryResult());
    }

    public function testGetAffectedRowsDelegatesToRowCount(): void
    {
        $stub = $this->createMock(PDOStatement::class);
        $stub->method('rowCount')->willReturn(5);

        $result = new Result();
        $result->initialize($stub, null);

        self::assertSame(5, $result->getAffectedRows());
    }

    public function testGetGeneratedValueReturnsInitializedValue(): void
    {
        $stub   = $this->createMock(PDOStatement::class);
        $result = new Result();
        $result->initialize($stub, 42);

        self::assertSame(42, $result->getGeneratedValue());
    }

    public function testGetGeneratedValueReturnsNullByDefault(): void
    {
        $result = new Result();

        self::assertNull($result->getGeneratedValue());
    }

    public function testRewindThrowsExceptionOnForwardOnlyAfterAdvancing(): void
    {
        $stub = $this->createMock(PDOStatement::class);
        $stub->method('fetch')->willReturn(['id' => 1]);

        $result = new Result();
        $result->initialize($stub, null);
        $result->setStatementMode(Result::STATEMENT_MODE_FORWARD);

        $result->rewind();
        $result->next();

        $this->expectException(RuntimeException::class);
        $result->rewind();
    }

    public function testNextAdvancesPositionAndFetchesData(): void
    {
        $stub = $this->createMock(PDOStatement::class);
        $stub->method('fetch')->willReturn(['name' => 'test']);

        $result = new Result();
        $result->initialize($stub, null);

        $result->rewind();
        self::assertSame(0, $result->key());

        $result->next();
        self::assertSame(1, $result->key());
    }

    public function testBufferIsCallableWithNoEffect(): void
    {
        $result = new Result();
        $result->buffer();

        self::assertFalse($result->isBuffered());
    }

    public function testSetFetchModeThrowsOnInvalidFetchMode(): void
    {
        $result = new Result();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The fetch mode must be one of the PDO::FETCH_* constants.');

        $result->setFetchMode(9999);
    }

    public function testSetFetchModeStoresValidMode(): void
    {
        $result = new Result();
        $result->setFetchMode(PDO::FETCH_NUM);

        self::assertSame(PDO::FETCH_NUM, $result->getFetchMode());
    }

    public function testValidReturnsFalseWhenCurrentDataIsFalse(): void
    {
        $stub = $this->createMock(PDOStatement::class);
        $stub->method('fetch')->willReturn(false);

        $result = new Result();
        $result->initialize($stub, null);
        $result->rewind();

        self::assertFalse($result->valid());
    }

    public function testValidReturnsTrueWhenCurrentDataExists(): void
    {
        $stub = $this->createMock(PDOStatement::class);
        $stub->method('fetch')->willReturn(['id' => 1]);

        $result = new Result();
        $result->initialize($stub, null);
        $result->rewind();

        self::assertTrue($result->valid());
    }
}
