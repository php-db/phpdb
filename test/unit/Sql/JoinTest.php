<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\Join;
use PhpDb\Sql\Select;
use PhpDbTest\DeprecatedAssertionsTrait;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use TypeError;

#[IgnoreDeprecations]
#[RequiresPhp('<= 8.6')]
#[CoversMethod(Join::class, 'rewind')]
#[CoversMethod(Join::class, 'current')]
#[CoversMethod(Join::class, 'key')]
#[CoversMethod(Join::class, 'next')]
#[CoversMethod(Join::class, 'valid')]
#[CoversMethod(Join::class, 'getJoins')]
#[CoversMethod(Join::class, 'join')]
#[CoversMethod(Join::class, 'count')]
#[CoversMethod(Join::class, 'reset')]
class JoinTest extends TestCase
{
    use DeprecatedAssertionsTrait;

    /** @return array<string, array{array<array-key, mixed>, string}> */
    public static function invalidJoinNameWithoutStringElementProvider(): array
    {
        return [
            'empty array'          => [[], 'null'],
            'select without alias' => [[new Select('foo')], Select::class],
            'integer element'      => [[5], 'int'],
        ];
    }

    #[Test]
    #[TestDox('unit test: Test count() returns correct count')]
    public function countsJoinedTables(): void
    {
        $join = new Join();
        $join->join('baz', 'foo.fooId = baz.fooId', Join::JOIN_LEFT);
        $join->join('bar', 'foo.fooId = bar.fooId', Join::JOIN_LEFT);

        static::assertSame(2, $join->count());
        static::assertCount($join->count(), $join->getJoins());
    }

    #[Test]
    public function currentReturnsTheCurrentJoinSpecification(): void
    {
        $name = 'baz';
        $on   = 'foo.id = baz.id';

        $join = new Join();
        $join->join($name, $on);

        $expectedSpecification = [
            'name'    => $name,
            'on'      => $on,
            'columns' => [Select::SQL_STAR],
            'type'    => Join::JOIN_INNER,
        ];

        static::assertEquals($expectedSpecification, $join->current());
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function initialPositionIsZero(): void
    {
        $join = new Join();

        static::assertAttributeSame(0, 'position', $join);
    }

    #[Test]
    #[TestDox('unit test: Test join() returns Join object (is chainable)')]
    public function join(): void
    {
        $join   = new Join();
        $return = $join->join('baz', 'foo.fooId = baz.fooId', Join::JOIN_LEFT);
        static::assertSame($join, $return);
    }

    #[Test]
    public function joinFullOuter(): void
    {
        $join   = new Join();
        $return = $join->join('baz', 'foo.fooId = baz.fooId', Join::JOIN_FULL_OUTER);
        static::assertSame($join, $return);
    }

    /**
     * @param array<array-key, mixed> $name
     */
    #[Test]
    #[DataProvider('invalidJoinNameWithoutStringElementProvider')]
    public function joinThrowsInvalidArgumentWhenInvalidNameHasNoLeadingString(array $name, string $described): void
    {
        $join = new Join();

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage("expects '{$described}' as a single element associative array");
        $join->join($name, 'on');
    }

    #[Test]
    public function joinThrowsOnInvalidMultiElementArray(): void
    {
        $join = new Join();

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage("expects 'b' as a single element associative array");
        $join->join(['a' => 'b', 'c' => 'd'], 'on');
    }

    #[Test]
    public function joinWillThrowAnExceptionIfNameIsNoValid(): void
    {
        $join = new Join();

        self::expectException(TypeError::class);
        /** @noinspection PhpArgumentWithoutNamedIdentifierInspection */
        $join->join([], false);
    }

    #[Test]
    public function keyReturnsTheCurrentPosition(): void
    {
        $join = new Join();

        $join->next();
        $join->next();
        $join->next();

        static::assertSame(3, $join->key());
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function nextIncrementsThePosition(): void
    {
        $join = new Join();

        $join->next();

        static::assertAttributeSame(1, 'position', $join);
    }

    #[Test]
    #[TestDox('unit test: Test reset() resets the joins')]
    public function reset(): void
    {
        $join = new Join();
        $join->join('baz', 'foo.fooId = baz.fooId', Join::JOIN_LEFT);
        $join->join('bar', 'foo.fooId = bar.fooId', Join::JOIN_LEFT);
        $join->reset();

        static::assertSame(0, $join->count());
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function rewindResetsPositionToZero(): void
    {
        $join = new Join();

        $join->next();
        $join->next();
        static::assertAttributeSame(2, 'position', $join);

        $join->rewind();
        static::assertAttributeSame(0, 'position', $join);
    }

    #[Test]
    public function validReturnsTrueIfTheIteratorIsAtAValidPositionAndFalseIfNot(): void
    {
        $join = new Join();
        $join->join('baz', 'foo.id = baz.id');

        static::assertTrue($join->valid());

        $join->next();

        static::assertFalse($join->valid());
    }
}
