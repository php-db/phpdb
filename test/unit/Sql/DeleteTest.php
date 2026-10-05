<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use Override;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Sql\AbstractPreparableSql;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Value;
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
use PhpDb\Sql\Where;
use PhpDbTest\AdapterTestTrait;
use PhpDbTest\DeprecatedAssertionsTrait;
use PhpDbTest\TestAsset\DeleteIgnore;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use ReflectionException;

#[IgnoreDeprecations]
#[RequiresPhp('<= 8.6')]
#[CoversMethod(Delete::class, '__construct')]
#[CoversMethod(Delete::class, 'from')]
#[CoversMethod(Delete::class, 'getRawState')]
#[CoversMethod(Delete::class, 'where')]
#[CoversMethod(Delete::class, 'processDelete')]
#[CoversMethod(Delete::class, 'processWhere')]
#[CoversMethod(Delete::class, '__get')]
#[CoversMethod(AbstractPreparableSql::class, 'unaliasTable')]
final class DeleteTest extends TestCase
{
    use AdapterTestTrait;
    use DeprecatedAssertionsTrait;

    protected Delete $delete;

    /** @return array<string, array{array<string, string|TableIdentifier>, string}> */
    public static function aliasedTableProvider(): array
    {
        return [
            'table name'       => [['f' => 'foo'], '"foo"'],
            'table identifier' => [['f' => new TableIdentifier('foo', 'sch')], '"sch"."foo"'],
        ];
    }

    #[Test]
    public function constructorWithTable(): void
    {
        $delete = new Delete('foo');
        static::assertSame('foo', $delete->getRawState('table'));
    }

    #[Test]
    public function constructorWithTableIdentifier(): void
    {
        $tableIdentifier = new TableIdentifier('foo', 'bar');
        $delete          = new Delete($tableIdentifier);
        static::assertEquals($tableIdentifier, $delete->getRawState('table'));
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function from(): void
    {
        // Set table with string
        $this->delete->from('foo');
        static::assertSame('foo', static::readAttribute($this->delete, 'table'));

        // Set table with TableIdentifier
        $tableIdentifier = new TableIdentifier('foo', 'bar');
        $this->delete->from($tableIdentifier);
        static::assertEquals($tableIdentifier, static::readAttribute($this->delete, 'table'));
    }

    #[Test]
    public function getRawState(): void
    {
        $this->delete->from('foo')->where('x = y');

        $rawState = $this->delete->getRawState();

        static::assertIsArray($rawState);
        static::assertArrayHasKey('table', $rawState);
        static::assertArrayHasKey('where', $rawState);
        static::assertArrayHasKey('emptyWhereProtection', $rawState);

        static::assertSame('foo', $rawState['table']);
        static::assertInstanceOf(Where::class, $rawState['where']);
        static::assertTrue($rawState['emptyWhereProtection']);
    }

    #[Test]
    public function getRawStateWithKey(): void
    {
        $this->delete->from('foo');

        static::assertSame('foo', $this->delete->getRawState('table'));
        static::assertInstanceOf(Where::class, $this->delete->getRawState('where'));
        static::assertTrue($this->delete->getRawState('emptyWhereProtection'));
    }

    #[Test]
    public function getSqlString(): void
    {
        $this->delete->from('foo')->where('x = y');
        static::assertSame('DELETE FROM "foo" WHERE x = y', $this->delete->getSqlString());

        // Test with TableIdentifier
        $this->delete = new Delete();
        $this->delete->from(new TableIdentifier('foo', 'sch'))->where('x = y');
        static::assertSame('DELETE FROM "sch"."foo" WHERE x = y', $this->delete->getSqlString());
    }

    /**
     * @param array<string, string|TableIdentifier> $table
     */
    #[Test]
    #[DataProvider('aliasedTableProvider')]
    public function getSqlStringRendersBareTableForAliasedTable(array $table, string $expected): void
    {
        $this->delete->from($table)->where('x = y');

        static::assertSame("DELETE FROM {$expected} WHERE x = y", $this->delete->getSqlString());
    }

    #[Test]
    public function getSqlStringWithEmptyWhere(): void
    {
        $this->delete->from('foo');
        // Empty where should not add WHERE clause
        static::assertSame('DELETE FROM "foo"', $this->delete->getSqlString());
    }

    #[Test]
    public function magicGetReturnsNullForUnknownProperty(): void
    {
        /** @noinspection PhpUndefinedFieldInspection */
        static::assertNull($this->delete->unknown); // @phpstan-ignore-line
        static::assertNull($this->delete->table); // @phpstan-ignore-line
    }

    #[Test]
    public function magicGetReturnsWhereClause(): void
    {
        $where = $this->delete->where;
        static::assertInstanceOf(Where::class, $where);
    }

    #[Test]
    public function prepareStatement(): void
    {
        $mockDriver  = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockAdapter = $this->createMockAdapter($mockDriver);

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $mockStatement->expects($this->once())
            ->method('setSql')
            ->with(static::equalTo('DELETE FROM "foo" WHERE x = y'));

        $this->delete->from('foo')->where('x = y');

        $this->delete->prepareStatement($mockAdapter, $mockStatement);

        // Test with TableIdentifier
        $this->delete = new Delete();

        $mockDriver  = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockAdapter = $this->createMockAdapter($mockDriver);

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $mockStatement->expects($this->once())
            ->method('setSql')
            ->with(static::equalTo('DELETE FROM "sch"."foo" WHERE x = y'));

        $this->delete->from(new TableIdentifier('foo', 'sch'))->where('x = y');

        $this->delete->prepareStatement($mockAdapter, $mockStatement);
    }

    #[Test]
    #[CoversNothing]
    public function specificationconstantsCouldBeOverridedByExtensionInGetSqlString(): void
    {
        $deleteIgnore = new DeleteIgnore();

        $deleteIgnore->from('foo')
            ->where('x = y');
        static::assertSame('DELETE IGNORE FROM "foo" WHERE x = y', $deleteIgnore->getSqlString());

        // with TableIdentifier
        $deleteIgnore = new DeleteIgnore();
        $deleteIgnore->from(new TableIdentifier('foo', 'sch'))
            ->where('x = y');
        static::assertSame('DELETE IGNORE FROM "sch"."foo" WHERE x = y', $deleteIgnore->getSqlString());
    }

    #[Test]
    #[CoversNothing]
    public function specificationconstantsCouldBeOverridedByExtensionInPrepareStatement(): void
    {
        $deleteIgnore = new DeleteIgnore();

        $mockDriver  = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockAdapter = $this->createMockAdapter($mockDriver);

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $mockStatement->expects($this->once())
            ->method('setSql')
            ->with(static::equalTo('DELETE IGNORE FROM "foo" WHERE x = y'));

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
            ->with(static::equalTo('DELETE IGNORE FROM "sch"."foo" WHERE x = y'));

        $deleteIgnore->from(new TableIdentifier('foo', 'sch'))
            ->where('x = y');

        $deleteIgnore->prepareStatement($mockAdapter, $mockStatement);
    }

    /**
     * @throws ReflectionException
     * @todo REMOVE THIS IN 3.x
     */
    #[Test]
    public function where(): void
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

        $predicates = static::readAttribute($where, 'predicates');
        static::assertSame('AND', $predicates[0][0]);
        static::assertInstanceOf(Literal::class, $predicates[0][1]);

        static::assertSame('AND', $predicates[1][0]);
        static::assertInstanceOf(Expression::class, $predicates[1][1]);

        static::assertSame('AND', $predicates[2][0]);
        static::assertInstanceOf(Operator::class, $predicates[2][1]);

        static::assertSame('OR', $predicates[3][0]);
        static::assertInstanceOf(Literal::class, $predicates[3][1]);

        static::assertSame('AND', $predicates[4][0]);
        static::assertInstanceOf(IsNull::class, $predicates[4][1]);

        static::assertSame('AND', $predicates[5][0]);
        static::assertInstanceOf(In::class, $predicates[5][1]);

        static::assertSame('AND', $predicates[6][0]);
        static::assertInstanceOf(IsNotNull::class, $predicates[6][1]);

        static::assertSame('AND', $predicates[7][0]);
        static::assertInstanceOf(Operator::class, $predicates[7][1]);

        static::assertSame('AND', $predicates[8][0]);
        static::assertInstanceOf(Operator::class, $predicates[8][1]);

        $where = new Where();
        $this->delete->where($where);
        static::assertSame($where, $this->delete->where);

        $this->delete->where(static function ($what) use ($where): void {
            self::assertSame($where, $what);
        });
    }

    #[Test]
    #[TestDox('unit test: Test where() accepts Expression (ExpressionInterface) in array')]
    public function whereAcceptsExpressionInterface(): void
    {
        $this->delete
            ->from('foo')
            ->where([
                new SqlExpression('COUNT(?) > ?', [new Identifier('id'), new Value(5)]),
            ]);

        $where = $this->delete->getRawState('where');
        static::assertInstanceOf(Where::class, $where);
        static::assertSame(1, $where->count());
    }

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
    protected function tearDown(): void {}
}
