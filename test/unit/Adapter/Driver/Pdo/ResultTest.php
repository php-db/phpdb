<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver\Pdo;

use PDO;
use PDOStatement;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Exception\InvalidArgumentException;
use PhpDb\Adapter\Exception\RuntimeException;
use PhpDb\ResultSet\ResultSet;
use PhpDbTest\TestAsset\TemporaryResultSet;
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
#[CoversMethod(Result::class, 'getQueryResult')]
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
    #[\PHPUnit\Framework\Attributes\Test]
    public function bufferIsCallableWithNoEffect(): void
    {
        $result = new Result();
        $result->buffer();

        static::assertFalse($result->isBuffered());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function countCachesResultFromClosure(): void
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

        static::assertSame(1, $callCount);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function countCachesResultFromStatementRowCount(): void
    {
        $stub = $this->getMockBuilder(PDOStatement::class)->getMock();
        $stub->expects($this->once())->method('rowCount')->willReturn(3);

        $result = new Result();
        $result->initialize($stub, null);

        $result->count();
        $result->count();

        static::assertSame(3, $result->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function countWithClosureInvokesClosureAndReturnsValue(): void
    {
        $stub = $this->getMockBuilder(PDOStatement::class)->getMock();
        $stub->expects($this->never())->method('rowCount');

        $rowCount = static fn(): int => 42;

        $result = new Result();
        $result->initialize($stub, null, $rowCount);

        static::assertSame(42, $result->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function countWithIntReturnsProvidedValue(): void
    {
        $stub = $this->getMockBuilder(PDOStatement::class)->getMock();
        $stub->expects($this->never())->method('rowCount');

        $result = new Result();
        $result->initialize($stub, null, 10);

        static::assertSame(10, $result->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function countWithNoRowCountFallsBackToStatementRowCount(): void
    {
        $stub = $this->getMockBuilder(PDOStatement::class)->getMock();
        $stub->expects($this->once())
            ->method('rowCount')
            ->willReturn(5);

        $result = new Result();
        $result->initialize($stub, null);

        static::assertSame(5, $result->count());
    }

    /**
     * Tests current method returns same data on consecutive calls.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function currentReturnsSameDataOnConsecutiveCalls(): void
    {
        $stub = $this->getMockBuilder('PDOStatement')->getMock();
        $stub->expects($this->any())
            ->method('fetch')
            ->willReturnCallback(static fn() => uniqid());

        $result = new Result();
        $result->initialize($stub, null);

        static::assertEquals($result->current(), $result->current());
    }

    /**
     * Tests whether the fetch mode has a broader range
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function fetchModeAcceptsNamedMode(): void
    {
        $stub = $this->getMockBuilder('PDOStatement')->getMock();
        $stub->expects($this->any())
            ->method('fetch')
            ->willReturnCallback(static fn() => new stdClass());
        $result = new Result();
        $result->initialize($stub, null);
        $result->setFetchMode(PDO::FETCH_NAMED);
        static::assertSame(11, $result->getFetchMode());
        static::assertInstanceOf('stdClass', $result->current());
    }

    /**
     * Tests whether the fetch mode was set properly and
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function fetchModeObjReturnsStdClass(): void
    {
        $stub = $this->getMockBuilder('PDOStatement')->getMock();
        $stub->expects($this->any())
            ->method('fetch')
            ->willReturnCallback(static fn() => new stdClass());

        $result = new Result();
        $result->initialize($stub, null);
        $result->setFetchMode(PDO::FETCH_OBJ);

        static::assertSame(5, $result->getFetchMode());
        static::assertInstanceOf('stdClass', $result->current());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getAffectedRowsDelegatesToRowCount(): void
    {
        $stub = $this->createMock(PDOStatement::class);
        $stub->method('rowCount')->willReturn(5);

        $result = new Result();
        $result->initialize($stub, null);

        static::assertSame(5, $result->getAffectedRows());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getFetchModeDefaultIsAssoc(): void
    {
        $result = new Result();

        static::assertSame(PDO::FETCH_ASSOC, $result->getFetchMode());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getFieldCountDelegatesToColumnCount(): void
    {
        $stub = $this->createMock(PDOStatement::class);
        $stub->method('columnCount')->willReturn(3);

        $result = new Result();
        $result->initialize($stub, null);

        static::assertSame(3, $result->getFieldCount());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getGeneratedValueReturnsInitializedValue(): void
    {
        $stub   = $this->createMock(PDOStatement::class);
        $result = new Result();
        $result->initialize($stub, 42);

        static::assertSame(42, $result->getGeneratedValue());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getGeneratedValueReturnsNullByDefault(): void
    {
        $result = new Result();

        static::assertNull($result->getGeneratedValue());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getQueryResultClonesGivenPrototypeRatherThanMutatingIt(): void
    {
        $stub = $this->createMock(PDOStatement::class);
        $stub->method('columnCount')->willReturn(3);

        $result = new Result();
        $result->initialize($stub, null);
        $prototype = new TemporaryResultSet();

        $returned = $result->getQueryResult($prototype);

        static::assertInstanceOf(TemporaryResultSet::class, $returned);
        static::assertNotSame($prototype, $returned);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getQueryResultInitializesReturnedResultSetWithThisResult(): void
    {
        $stub = $this->createMock(PDOStatement::class);
        $stub->method('columnCount')->willReturn(3);

        $result = new Result();
        $result->initialize($stub, null);

        static::assertSame($result->getFieldCount(), $result->getQueryResult()->getFieldCount());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getQueryResultReturnsDefaultResultSetPrototypeWhenNoneGiven(): void
    {
        $stub = $this->createMock(PDOStatement::class);
        $stub->method('columnCount')->willReturn(3);

        $result = new Result();
        $result->initialize($stub, null);

        static::assertInstanceOf(ResultSet::class, $result->getQueryResult());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getQueryResultThrowsWhenResultIsNotAQueryResult(): void
    {
        $stub = $this->createMock(PDOStatement::class);
        $stub->method('columnCount')->willReturn(0);

        $result = new Result();
        $result->initialize($stub, null);

        self::expectException(RuntimeException::class);
        $result->getQueryResult();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getResourceReturnsPdoStatement(): void
    {
        $stub   = $this->createMock(PDOStatement::class);
        $result = new Result();
        $result->initialize($stub, null);

        static::assertSame($stub, $result->getResource());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function initializeStoresResourceAndValues(): void
    {
        $stub   = $this->createMock(PDOStatement::class);
        $result = new Result();

        $result->initialize($stub, 42, 5);

        static::assertSame(42, $result->getGeneratedValue());
        static::assertSame(5, $result->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isBufferedReturnsFalse(): void
    {
        $result = new Result();

        static::assertFalse($result->isBuffered());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isQueryResultReturnsFalseWhenNoColumns(): void
    {
        $stub = $this->createMock(PDOStatement::class);
        $stub->method('columnCount')->willReturn(0);

        $result = new Result();
        $result->initialize($stub, null);

        static::assertFalse($result->isQueryResult());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isQueryResultReturnsTrueWhenColumnsExist(): void
    {
        $stub = $this->createMock(PDOStatement::class);
        $stub->method('columnCount')->willReturn(3);

        $result = new Result();
        $result->initialize($stub, null);

        static::assertTrue($result->isQueryResult());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function nextAdvancesPositionAndFetchesData(): void
    {
        $stub = $this->createMock(PDOStatement::class);
        $stub->method('fetch')->willReturn(['name' => 'test']);

        $result = new Result();
        $result->initialize($stub, null);

        $result->rewind();
        static::assertSame(0, $result->key());

        $result->next();
        static::assertSame(1, $result->key());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function rewindResetsIterationToStart(): void
    {
        $data = [
            ['test' => 1],
            ['test' => 2],
        ];
        $position = 0;

        $stub = $this->getMockBuilder('PDOStatement')->getMock();
        assert($stub instanceof PDOStatement); // to suppress IDE type warnings
        $stub->expects($this->any())
            ->method('fetch')
            ->willReturnCallback(static function () use ($data, &$position) {
                return $data[$position++];
            });
        $result = new Result();
        $result->initialize($stub, null);

        $result->rewind();
        $result->rewind();

        static::assertSame(0, $result->key());
        static::assertSame(1, $position);
        static::assertEquals($data[0], $result->current());

        $result->next();
        static::assertSame(1, $result->key());
        static::assertSame(2, $position);
        static::assertEquals($data[1], $result->current());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function rewindThrowsExceptionOnForwardOnlyAfterAdvancing(): void
    {
        $stub = $this->createMock(PDOStatement::class);
        $stub->method('fetch')->willReturn(['id' => 1]);

        $result = new Result();
        $result->initialize($stub, null);
        $result->setStatementMode(Result::STATEMENT_MODE_FORWARD);

        $result->rewind();
        $result->next();

        self::expectException(RuntimeException::class);
        $result->rewind();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function setFetchModeStoresValidMode(): void
    {
        $result = new Result();
        $result->setFetchMode(PDO::FETCH_NUM);

        static::assertSame(PDO::FETCH_NUM, $result->getFetchMode());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function setFetchModeThrowsOnInvalidFetchMode(): void
    {
        $result = new Result();

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::INVALID_FETCH_MODE);

        $result->setFetchMode(9999);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function setFetchModeThrowsOnInvalidMode(): void
    {
        $result = new Result();

        self::expectException(InvalidArgumentException::class);
        $result->setFetchMode(13);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function setStatementModeThrowsOnInvalidMode(): void
    {
        $result = new Result();

        self::expectException(InvalidArgumentException::class);
        $result->setStatementMode('invalid');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function setStatementModeToForward(): void
    {
        $result = new Result();

        $result->setStatementMode(Result::STATEMENT_MODE_FORWARD);

        static::assertSame(Result::STATEMENT_MODE_FORWARD, $result->getStatementMode());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function setStatementModeToScrollable(): void
    {
        $result = new Result();

        $result->setStatementMode(Result::STATEMENT_MODE_SCROLLABLE);

        static::assertSame(Result::STATEMENT_MODE_SCROLLABLE, $result->getStatementMode());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validReturnsFalseWhenCurrentDataIsFalse(): void
    {
        $stub = $this->createMock(PDOStatement::class);
        $stub->method('fetch')->willReturn(false);

        $result = new Result();
        $result->initialize($stub, null);
        $result->rewind();

        static::assertFalse($result->valid());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validReturnsTrueWhenCurrentDataExists(): void
    {
        $stub = $this->createMock(PDOStatement::class);
        $stub->method('fetch')->willReturn(['id' => 1]);

        $result = new Result();
        $result->initialize($stub, null);
        $result->rewind();

        static::assertTrue($result->valid());
    }
}
