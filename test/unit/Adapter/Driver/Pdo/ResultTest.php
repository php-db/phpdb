<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Driver\Pdo;

use PDO;
use PDOStatement;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use stdClass;

use function assert;
use function uniqid;

#[CoversMethod(Result::class, 'current')]
#[CoversMethod(Result::class, 'count')]
#[Group('result-pdo')]
final class ResultTest extends TestCase
{
    /**
     * Tests current method returns same data on consecutive calls.
     */
    public function testCurrent(): void
    {
        $stub = $this->getMockBuilder('PDOStatement')->getMock();
        $stub->expects($this->any())
            ->method('fetch')
            ->willReturnCallback(fn() => uniqid());

        $result = new Result();
        $result->initialize($stub, null);

        self::assertEquals($result->current(), $result->current());
    }

    public function testFetchModeException(): void
    {
        $result = new Result();

        $this->expectException(InvalidArgumentException::class);
        $result->setFetchMode(13);
    }

    /**
     * Tests whether the fetch mode was set properly and
     */
    public function testFetchModeAnonymousObject(): void
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
    public function testFetchModeRange(): void
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

    public function testMultipleRewind(): void
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
}
