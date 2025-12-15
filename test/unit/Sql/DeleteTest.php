<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use Override;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\Clause\Where;
use PhpDb\Sql\Delete;
use PhpDb\Sql\Expression as SqlExpression;
use PhpDb\Sql\Predicate\Expression;
use PhpDb\Sql\Predicate\In;
use PhpDb\Sql\Predicate\IsNotNull;
use PhpDb\Sql\Predicate\IsNull;
use PhpDb\Sql\Predicate\Literal;
use PhpDb\Sql\Predicate\Operator;
use PhpDb\Sql\Predicate\PredicateSet;
use PhpDb\Sql\TableIdentifier;
use PhpDbTest\AdapterTestTrait;
use PhpDbTest\DeprecatedAssertionsTrait;
use PhpDbTest\TestAsset\DeleteIgnore;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use ReflectionException;

#[CoversMethod(Delete::class, '__construct')]
#[CoversMethod(Delete::class, 'from')]
#[CoversMethod(Delete::class, 'getRawState')]
#[CoversMethod(Delete::class, 'where')]
#[CoversMethod(Delete::class, 'processDelete')]
#[CoversMethod(Delete::class, 'processWhere')]
#[CoversMethod(Delete::class, '__get')]
final class DeleteTest extends TestCase
{
    use AdapterTestTrait;
    use DeprecatedAssertionsTrait;

    protected Delete $delete;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->delete = new Delete();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    /**
     * @throws ReflectionException
     */
    public function testFrom(): void
    {
        // Set table with string
        $this->delete->from('foo');
        self::assertEquals('foo', $this->readAttribute($this->delete, 'table'));

        // Set table with TableIdentifier
        $tableIdentifier = new TableIdentifier('foo', 'bar');
        $this->delete->from($tableIdentifier);
        self::assertEquals($tableIdentifier, $this->readAttribute($this->delete, 'table'));
    }

    /**
     * @throws ReflectionException
     * @todo REMOVE THIS IN 3.x
     */
    public function testWhere(): void
    {
        $this->delete->where('x = y');
        $this->delete->where(['foo > ?' => 5]);
        $this->delete->where(['id' => 2]);
        $this->delete->where(['a = b'], PredicateSet::OP_OR);
        $this->delete->where(['c1' => null]);
        $this->delete->where(['c2' => [1, 2, 3]]);
        $this->delete->where([new IsNotNull('c3')]);
        $this->delete->where(['one' => 1, 'two' => 2]);

        $where = $this->delete->where;

        $predicates = $this->readAttribute($where, 'predicates');
        self::assertEquals('AND', $predicates[0][0]);
        self::assertInstanceOf(Literal::class, $predicates[0][1]);

        self::assertEquals('AND', $predicates[1][0]);
        self::assertInstanceOf(Expression::class, $predicates[1][1]);

        self::assertEquals('AND', $predicates[2][0]);
        self::assertInstanceOf(Operator::class, $predicates[2][1]);

        self::assertEquals('OR', $predicates[3][0]);
        self::assertInstanceOf(Literal::class, $predicates[3][1]);

        self::assertEquals('AND', $predicates[4][0]);
        self::assertInstanceOf(IsNull::class, $predicates[4][1]);

        self::assertEquals('AND', $predicates[5][0]);
        self::assertInstanceOf(In::class, $predicates[5][1]);

        self::assertEquals('AND', $predicates[6][0]);
        self::assertInstanceOf(IsNotNull::class, $predicates[6][1]);

        self::assertEquals('AND', $predicates[7][0]);
        self::assertInstanceOf(Operator::class, $predicates[7][1]);

        self::assertEquals('AND', $predicates[8][0]);
        self::assertInstanceOf(Operator::class, $predicates[8][1]);

        $where = new Where();
        $this->delete->where($where);
        self::assertSame($where, $this->delete->where);

        $this->delete->where(function ($what) use ($where): void {
            self::assertSame($where, $what);
        });
    }

    public function testPrepareStatement(): void
    {
        $mockDriver  = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockAdapter = $this->createMockAdapter($mockDriver);

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $mockStatement->expects($this->once())
            ->method('setSql')
            ->with($this->equalTo('DELETE FROM "foo" WHERE x = y'));

        $this->delete->from('foo')
            ->where('x = y');

        $this->delete->prepareStatement($mockAdapter, $mockStatement);

        // Test with TableIdentifier
        $this->delete = new Delete();

        $mockDriver  = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockAdapter = $this->createMockAdapter($mockDriver);

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $mockStatement->expects($this->once())
            ->method('setSql')
            ->with($this->equalTo('DELETE FROM "sch"."foo" WHERE x = y'));

        $this->delete->from(new TableIdentifier('foo', 'sch'))
            ->where('x = y');

        $this->delete->prepareStatement($mockAdapter, $mockStatement);
    }

    public function testGetSqlString(): void
    {
        $this->delete->from('foo')
            ->where('x = y');
        self::assertEquals('DELETE FROM "foo" WHERE x = y', $this->delete->getSqlString());

        // Test with TableIdentifier
        $this->delete = new Delete();
        $this->delete->from(new TableIdentifier('foo', 'sch'))
            ->where('x = y');
        self::assertEquals('DELETE FROM "sch"."foo" WHERE x = y', $this->delete->getSqlString());
    }

    /**
     * @deprecated SPECIFICATION_* constants no longer exist in new architecture
     */
    #[CoversNothing]
    #[RequiresMethod(Delete::class, 'processDelete')]
    public function testSpecificationconstantsCouldBeOverridedByExtensionInPrepareStatement(): void
    {
        $deleteIgnore = new DeleteIgnore();

        $mockDriver  = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockAdapter = $this->createMockAdapter($mockDriver);

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $mockStatement->expects($this->once())
            ->method('setSql')
            ->with($this->equalTo('DELETE IGNORE FROM "foo" WHERE x = y'));

        $deleteIgnore->from('foo')
            ->where('x = y');

        $deleteIgnore->prepareStatement($mockAdapter, $mockStatement);

        // with TableIdentifier
        $deleteIgnore = new DeleteIgnore();

        $mockDriver  = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockAdapter = $this->createMockAdapter($mockDriver);

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $mockStatement->expects($this->once())
            ->method('setSql')
            ->with($this->equalTo('DELETE IGNORE FROM "sch"."foo" WHERE x = y'));

        $deleteIgnore->from(new TableIdentifier('foo', 'sch'))
            ->where('x = y');

        $deleteIgnore->prepareStatement($mockAdapter, $mockStatement);
    }

    /**
     * @deprecated SPECIFICATION_* constants no longer exist in new architecture
     */
    #[CoversNothing]
    #[RequiresMethod(Delete::class, 'processDelete')]
    public function testSpecificationconstantsCouldBeOverridedByExtensionInGetSqlString(): void
    {
        $deleteIgnore = new DeleteIgnore();

        $deleteIgnore->from('foo')
            ->where('x = y');
        self::assertEquals('DELETE IGNORE FROM "foo" WHERE x = y', $deleteIgnore->getSqlString());

        // with TableIdentifier
        $deleteIgnore = new DeleteIgnore();
        $deleteIgnore->from(new TableIdentifier('foo', 'sch'))
            ->where('x = y');
        self::assertEquals('DELETE IGNORE FROM "sch"."foo" WHERE x = y', $deleteIgnore->getSqlString());
    }

    public function testGetRawState(): void
    {
        $this->delete->from('foo')
            ->where('x = y');

        $rawState = $this->delete->getRawState();

        self::assertIsArray($rawState);
        self::assertArrayHasKey('table', $rawState);
        self::assertArrayHasKey('where', $rawState);

        self::assertInstanceOf(TableIdentifier::class, $rawState['table']);
        self::assertEquals('foo', $rawState['table']->getTable());
        self::assertInstanceOf(Where::class, $rawState['where']);
    }

    public function testGetRawStateWithKey(): void
    {
        $this->delete->from('foo');

        self::assertInstanceOf(TableIdentifier::class, $this->delete->getRawState('table'));
        self::assertEquals('foo', $this->delete->getRawState('table')->getTable());
        // where is null until accessed via where() or the magic property
        self::assertNull($this->delete->getRawState('where'));
        // Accessing the magic property initializes the where clause
        $where = $this->delete->where;
        self::assertInstanceOf(Where::class, $where);
        self::assertInstanceOf(Where::class, $this->delete->getRawState('where'));
    }

    public function testMagicGetReturnsWhereClause(): void
    {
        $where = $this->delete->where;
        self::assertInstanceOf(Where::class, $where);
    }

    public function testMagicGetReturnsNullForUnknownProperty(): void
    {
        /** @noinspection PhpUndefinedFieldInspection */
        self::assertNull($this->delete->unknown); // @phpstan-ignore-line
        self::assertNull($this->delete->table); // @phpstan-ignore-line
    }

    public function testConstructorWithTable(): void
    {
        $delete = new Delete('foo');
        self::assertEquals('foo', $delete->getRawState('table'));
    }

    public function testConstructorWithTableIdentifier(): void
    {
        $tableIdentifier = new TableIdentifier('foo', 'bar');
        $delete          = new Delete($tableIdentifier);
        self::assertEquals($tableIdentifier, $delete->getRawState('table'));
    }

    public function testGetSqlStringWithEmptyWhere(): void
    {
        $this->delete->from('foo');
        $this->delete->where->setEmptyAllowed();
        // Empty where should not add WHERE clause
        self::assertEquals('DELETE FROM "foo"', $this->delete->getSqlString());
    }

    #[TestDox('unit test: Test where() accepts Expression (ExpressionInterface) in array')]
    public function testWhereAcceptsExpressionInterface(): void
    {
        $this->delete->from('foo')
            ->where([
                new SqlExpression('COUNT(?) > ?', [new Identifier('id'), new Value(5)]),
            ]);

        $where = $this->delete->getRawState('where');
        self::assertInstanceOf(Where::class, $where);
        self::assertEquals(1, $where->count());
    }
}
