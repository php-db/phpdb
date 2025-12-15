<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use PhpDb\Sql\Clause\JoinClause as Join;
use PhpDb\Sql\Clause\JoinSpecification;
use PhpDb\Sql\Clause\JoinType;
use PhpDb\Sql\Predicate;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;
use PhpDbTest\DeprecatedAssertionsTrait;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use TypeError;

#[CoversMethod(Join::class, '__construct')]
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

    /**
     * @throws ReflectionException
     */
    public function testInitialPositionIsZero(): void
    {
        $join = new Join();

        self::assertAttributeEquals(0, 'position', $join);
    }

    /**
     * @throws ReflectionException
     */
    public function testNextIncrementsThePosition(): void
    {
        $join = new Join();

        $join->next();

        self::assertAttributeEquals(1, 'position', $join);
    }

    /**
     * @throws ReflectionException
     */
    public function testRewindResetsPositionToZero(): void
    {
        $join = new Join();

        $join->next();
        $join->next();
        self::assertAttributeEquals(2, 'position', $join);

        $join->rewind();
        self::assertAttributeEquals(0, 'position', $join);
    }

    public function testKeyReturnsTheCurrentPosition(): void
    {
        $join = new Join();

        $join->next();
        $join->next();
        $join->next();

        self::assertEquals(3, $join->key());
    }

    public function testCurrentReturnsTheCurrentJoinSpecification(): void
    {
        $name = 'baz';
        $on   = 'foo.id = baz.id';

        $join = new Join();
        $join->join($name, $on);

        $current = $join->current();
        self::assertInstanceOf(JoinSpecification::class, $current);
        self::assertInstanceOf(Predicate\PredicateInterface::class, $current->on);
        self::assertEquals([Select::SQL_STAR], $current->columns);
        self::assertEquals(JoinType::Inner, $current->type);
        self::assertInstanceOf(TableIdentifier::class, $current->name);
        self::assertEquals($name, $current->name->getTable());
    }

    public function testValidReturnsTrueIfTheIteratorIsAtAValidPositionAndFalseIfNot(): void
    {
        $join = new Join();
        $join->join('baz', 'foo.id = baz.id');

        self::assertTrue($join->valid());

        $join->next();

        self::assertFalse($join->valid());
    }

    #[TestDox('unit test: Test join() returns Join object (is chainable)')]
    public function testJoin(): void
    {
        $join   = new Join();
        $return = $join->join('baz', 'foo.fooId = baz.fooId', Join::JOIN_LEFT);
        self::assertSame($join, $return);
    }

    public function testJoinFullOuter(): void
    {
        $join   = new Join();
        $return = $join->join('baz', 'foo.fooId = baz.fooId', Join::JOIN_FULL_OUTER);
        self::assertSame($join, $return);
    }

    public function testJoinWillThrowAnExceptionIfNameIsNoValid(): void
    {
        $join = new Join();

        $this->expectException(TypeError::class);
        /** @noinspection PhpArgumentWithoutNamedIdentifierInspection */
        $join->join([], false);
    }

    #[TestDox('unit test: Test count() returns correct count')]
    public function testCount(): void
    {
        $join = new Join();
        $join->join('baz', 'foo.fooId = baz.fooId', Join::JOIN_LEFT);
        $join->join('bar', 'foo.fooId = bar.fooId', Join::JOIN_LEFT);

        self::assertEquals(2, $join->count());
        self::assertCount($join->count(), $join->getJoins());
    }

    #[TestDox('unit test: Test reset() resets the joins')]
    public function testReset(): void
    {
        $join = new Join();
        $join->join('baz', 'foo.fooId = baz.fooId', Join::JOIN_LEFT);
        $join->join('bar', 'foo.fooId = bar.fooId', Join::JOIN_LEFT);
        $join->reset();

        self::assertEquals(0, $join->count());
    }
}
