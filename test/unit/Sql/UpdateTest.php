<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use Override;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\StatementContainer;
use PhpDb\Sql\AbstractPreparableSql;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\Expression;
use PhpDb\Sql\Join;
use PhpDb\Sql\Predicate\In;
use PhpDb\Sql\Predicate\IsNotNull;
use PhpDb\Sql\Predicate\IsNull;
use PhpDb\Sql\Predicate\Literal;
use PhpDb\Sql\Predicate\Operator;
use PhpDb\Sql\Predicate\PredicateSet;
use PhpDb\Sql\TableIdentifier;
use PhpDb\Sql\Update;
use PhpDb\Sql\Where;
use PhpDbTest\AdapterTestTrait;
use PhpDbTest\DeprecatedAssertionsTrait;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PhpDbTest\TestAsset\UpdateIgnore;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use TypeError;

#[IgnoreDeprecations]
#[RequiresPhp('<= 8.6')]
#[CoversMethod(AbstractPreparableSql::class, 'prepareStatement')]
#[CoversMethod(Update::class, 'table')]
#[CoversMethod(Update::class, '__construct')]
#[CoversMethod(Update::class, 'set')]
#[CoversMethod(Update::class, 'where')]
#[CoversMethod(Update::class, 'getRawState')]
#[CoversMethod(Update::class, 'prepareStatement')]
#[CoversMethod(Update::class, 'getSqlString')]
#[CoversMethod(Update::class, '__get')]
#[CoversMethod(Update::class, '__clone')]
#[CoversMethod(Update::class, 'join')]
#[CoversMethod(Update::class, 'processUpdate')]
#[CoversMethod(Update::class, 'processSet')]
#[CoversMethod(Update::class, 'processWhere')]
#[CoversMethod(Update::class, 'processJoins')]
#[CoversMethod(AbstractPreparableSql::class, 'unaliasTable')]
final class UpdateTest extends TestCase
{
    use AdapterTestTrait;
    use DeprecatedAssertionsTrait;

    protected Update $update;

    /** @return array<string, array{array<string, string|TableIdentifier>, string}> */
    public static function aliasedTableProvider(): array
    {
        return [
            'table name'       => [['f' => 'foo'], '"foo"'],
            'table identifier' => [['f' => new TableIdentifier('foo', 'sch')], '"sch"."foo"'],
        ];
    }

    #[Test]
    public function cloneDeepCopiesSetWhereAndJoins(): void
    {
        $this->update
            ->table('foo')
            ->set(['bar' => 'baz'])
            ->where('x = y')
            ->join('other', 'foo.id = other.id');

        $clone = clone $this->update;

        $clone->set(['bar' => 'changed']);
        $clone->where('z = w');
        $clone->join('another', 'foo.id = another.id');

        static::assertEquals(['bar' => 'baz'], $this->update->getRawState('set'));
        static::assertEquals(['bar' => 'changed'], $clone->getRawState('set'));

        static::assertNotSame(
            $this->update->getRawState('where'),
            $clone->getRawState('where'),
        );

        static::assertNotSame(
            $this->update->getRawState('joins'),
            $clone->getRawState('joins'),
        );
    }

    #[Test]
    public function cloneUpdate(): void
    {
        $update1 = clone $this->update;
        $update1->table('foo')
            ->set(['bar' => 'baz'])
            ->where('x = y');

        $update2 = clone $this->update;
        $update2->table('foo')
            ->set(['bar' => 'baz'])
            ->where([
                'id = ?' => 1,
            ]);
        static::assertSame(
            'UPDATE "foo" SET "bar" = \'baz\' WHERE id = \'1\'',
            $update2->getSqlString(new TrustingSql92Platform()),
        );
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function construct(): void
    {
        $update = new Update('foo');
        static::assertSame('foo', static::readAttribute($update, 'table'));
    }

    #[Test]
    public function constructWithTableIdentifier(): void
    {
        $tableIdentifier = new TableIdentifier('foo', 'bar');
        $update          = new Update($tableIdentifier);

        static::assertEquals($tableIdentifier, $update->getRawState('table'));
    }

    #[Test]
    public function getRawState(): void
    {
        $this->update
            ->table('foo')
            ->set(['bar' => 'baz'])
            ->where('x = y');

        static::assertSame('foo', $this->update->getRawState('table'));
        static::assertTrue($this->update->getRawState('emptyWhereProtection'));
        static::assertEquals(['bar' => 'baz'], $this->update->getRawState('set'));
        static::assertInstanceOf(Where::class, $this->update->getRawState('where'));
    }

    #[Test]
    public function getRawStateReturnsAllState(): void
    {
        $this->update
            ->table('foo')
            ->set(['bar' => 'baz'])
            ->where('x = y');

        $rawState = $this->update->getRawState();

        static::assertIsArray($rawState);
        static::assertArrayHasKey('table', $rawState);
        static::assertArrayHasKey('set', $rawState);
        static::assertArrayHasKey('where', $rawState);
        static::assertArrayHasKey('emptyWhereProtection', $rawState);
        static::assertArrayHasKey('joins', $rawState);

        static::assertSame('foo', $rawState['table']);
        static::assertEquals(['bar' => 'baz'], $rawState['set']);
        static::assertInstanceOf(Where::class, $rawState['where']);
        static::assertInstanceOf(Join::class, $rawState['joins']);
        static::assertTrue($rawState['emptyWhereProtection']);
    }

    #[Test]
    public function getSqlString(): void
    {
        $this->update
            ->table('foo')
            ->set(['bar' => 'baz', 'boo' => new Expression('NOW()'), 'bam' => null])
            ->where('x = y');

        static::assertSame(
            'UPDATE "foo" SET "bar" = \'baz\', "boo" = NOW(), "bam" = NULL WHERE x = y',
            $this->update->getSqlString(new TrustingSql92Platform()),
        );

        // with TableIdentifier
        $this->update = new Update();
        $this->update
            ->table(new TableIdentifier('foo', 'sch'))
            ->set(['bar' => 'baz', 'boo' => new Expression('NOW()'), 'bam' => null])
            ->where('x = y');

        static::assertSame(
            'UPDATE "sch"."foo" SET "bar" = \'baz\', "boo" = NOW(), "bam" = NULL WHERE x = y',
            $this->update->getSqlString(new TrustingSql92Platform()),
        );
    }

    #[Test]
    #[Group('6768')]
    #[Group('6773')]
    public function getSqlStringForFalseUpdateValueParameter(): void
    {
        $this->update = new Update();
        $this->update
            ->table(new TableIdentifier('foo', 'sch'))
            ->set(['bar' => false, 'boo' => 'test', 'bam' => true])
            ->where('x = y');
        static::assertSame(
            'UPDATE "sch"."foo" SET "bar" = \'\', "boo" = \'test\', "bam" = \'1\' WHERE x = y',
            $this->update->getSqlString(new TrustingSql92Platform()),
        );
    }

    /**
     * @param array<string, string|TableIdentifier> $table
     */
    #[Test]
    #[DataProvider('aliasedTableProvider')]
    public function getSqlStringRendersBareTableForAliasedTable(array $table, string $expected): void
    {
        $this->update->table($table)->set(['bar' => 'baz']);

        static::assertSame(
            "UPDATE {$expected} SET \"bar\" = 'baz'",
            $this->update->getSqlString(new TrustingSql92Platform()),
        );
    }

    #[Test]
    public function getSqlStringWithEmptyWhere(): void
    {
        $this->update->table('foo')
            ->set(['bar' => 'baz']);

        static::assertSame(
            'UPDATE "foo" SET "bar" = \'baz\'',
            $this->update->getSqlString(new TrustingSql92Platform()),
        );
    }

    #[Test]
    public function getUpdate(): void
    {
        $getWhere = $this->update->__get('where');
        static::assertInstanceOf(Where::class, $getWhere);
    }

    #[Test]
    public function getUpdateFails(): void
    {
        /** @psalm-suppress UndefinedThisPropertyFetch - Ensure non-existent property returns null */
        $getWhat = $this->update->__get('what');
        static::assertNull($getWhat);
    }

    #[Test]
    public function join(): void
    {
        $this->update->table('Document');
        $this->update
            ->set(['x' => 'y'])
            ->join(
                'User', // table name
                'User.UserId = Document.UserId', // expression to join on
                // default JOIN INNER
            )
            ->join(
                'Category',
                'Category.CategoryId = Document.CategoryId',
                Join::JOIN_LEFT, // (optional), one of inner, outer, left, right
            );

        static::assertEquals(
            'UPDATE "Document" INNER JOIN "User" ON "User"."UserId" = "Document"."UserId" '
                . 'LEFT JOIN "Category" ON "Category"."CategoryId" = "Document"."CategoryId" SET "x" = \'y\'',
            $this->update->getSqlString(new TrustingSql92Platform()),
        );
    }

    #[Test]
    #[TestDox('unit test: Test join() returns Update object (is chainable)')]
    public function joinChainable(): void
    {
        $return = $this->update->join('baz', 'foo.fooId = baz.fooId', Join::JOIN_LEFT);
        static::assertSame($this->update, $return);
    }

    /**
     * Here test if we want update fields from specific table.
     * Important when we're updating fields that are existing in several tables in one query.
     * The same test as above but here we will specify table in update params
     */
    #[Test]
    public function joinMultiUpdate(): void
    {
        $this->update->table('Document');
        $this->update
            ->set(['Documents.x' => 'y'])
            ->join(
                'User',
                'User.UserId = Document.UserId',
            )
            ->join(
                'Category',
                'Category.CategoryId = Document.CategoryId',
                Join::JOIN_LEFT,
            );

        static::assertEquals(
            'UPDATE "Document" INNER JOIN "User" ON "User"."UserId" = "Document"."UserId" '
                . 'LEFT JOIN "Category" ON "Category"."CategoryId" = "Document"."CategoryId" SET "Documents"."x" = \'y\'',
            $this->update->getSqlString(new TrustingSql92Platform()),
        );
    }

    #[Test]
    public function joinWithTableIdentifier(): void
    {
        $this->update
            ->table('foo')
            ->set(['x' => 'y'])
            ->join(new TableIdentifier('bar', 'schema'), 'foo.id = bar.foo_id');

        $sql = $this->update->getSqlString(new TrustingSql92Platform());
        static::assertStringContainsString('JOIN "schema"."bar"', $sql);
    }

    #[Test]
    #[Group('Laminas-240')]
    public function passingMultipleKeyValueInWhereClause(): void
    {
        $update = clone $this->update;
        $update->table('table');
        $update->set(['fld1' => 'val1']);
        $update->where(['id1' => 'val1', 'id2' => 'val2']);
        static::assertSame(
            'UPDATE "table" SET "fld1" = \'val1\' WHERE "id1" = \'val1\' AND "id2" = \'val2\'',
            $update->getSqlString(new TrustingSql92Platform()),
        );
    }

    #[Test]
    public function prepareStatement(): void
    {
        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('getPrepareType')->willReturn('positional');
        $mockDriver->expects($this->any())->method('formatParameterName')->willReturn('?');
        $mockAdapter = $this->createMockAdapter($mockDriver);

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $pContainer    = new ParameterContainer([]);
        $mockStatement->expects($this->any())->method('getParameterContainer')->willReturn($pContainer);

        $mockStatement->expects($this->once())
            ->method('setSql')
            ->with(static::equalTo('UPDATE "foo" SET "bar" = ?, "boo" = NOW() WHERE x = y'));

        $this->update
            ->table('foo')
            ->set(['bar' => 'baz', 'boo' => new Expression('NOW()')])
            ->where('x = y');

        $this->update->prepareStatement($mockAdapter, $mockStatement);

        // with TableIdentifier
        $this->update = new Update();

        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('getPrepareType')->willReturn('positional');
        $mockDriver->expects($this->any())->method('formatParameterName')->willReturn('?');
        $mockAdapter = $this->createMockAdapter($mockDriver);

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $pContainer    = new ParameterContainer([]);
        $mockStatement->expects($this->any())->method('getParameterContainer')->willReturn($pContainer);

        $mockStatement->expects($this->once())
            ->method('setSql')
            ->with(static::equalTo('UPDATE "sch"."foo" SET "bar" = ?, "boo" = NOW() WHERE x = y'));

        $this->update
            ->table(new TableIdentifier('foo', 'sch'))
            ->set(['bar' => 'baz', 'boo' => new Expression('NOW()')])
            ->where('x = y');

        $this->update->prepareStatement($mockAdapter, $mockStatement);
    }

    #[Test]
    public function processSetWithPdoDriverUsesDriverColumnQuoting(): void
    {
        $mockDriver = $this->getMockBuilder(PdoDriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('getPrepareType')->willReturn('positional');
        $mockDriver->expects($this->any())
            ->method('formatParameterName')
            ->willReturnCallback(static fn(string $name): string => ":{$name}");
        $mockAdapter = $this->createMockAdapter($mockDriver);

        $mockStatement = new StatementContainer();

        $this->update->table('foo')
            ->set(['bar' => 'baz', 'boo' => 'qux']);

        $this->update->prepareStatement($mockAdapter, $mockStatement);

        $sql = $mockStatement->getSql();
        static::assertStringContainsString(':c_0', $sql);
        static::assertStringContainsString(':c_1', $sql);
    }

    #[Test]
    public function set(): void
    {
        $this->update->set(['foo' => 'bar']);
        static::assertEquals(['foo' => 'bar'], $this->update->getRawState('set'));
    }

    #[Test]
    public function setWithMergeFlag(): void
    {
        $this->update->set(['foo' => 'bar']);
        $this->update->set(['baz' => 'qux'], Update::VALUES_MERGE);

        $set = $this->update->getRawState('set');
        static::assertEquals(['foo' => 'bar', 'baz' => 'qux'], $set);
    }

    #[Test]
    public function setWithNonStringKeyThrowsException(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::NON_STRING_VALUE_KEY);

        /** @psalm-suppress InvalidArgument - Testing invalid argument handling */
        $this->update->set([0 => 'value']);
    }

    #[Test]
    public function setWithNumericPriority(): void
    {
        $this->update->set(['three' => 'c'], 30);
        $this->update->set(['one' => 'a'], 10);
        $this->update->set(['two' => 'b'], 20);

        $set = $this->update->getRawState('set');
        static::assertEquals(['one' => 'a', 'two' => 'b', 'three' => 'c'], $set);
    }

    #[Test]
    public function sortableSet(): void
    {
        $this->update->set([
            'two'   => 'с_two',
            'three' => 'с_three',
        ]);
        $this->update->set(['one' => 'с_one'], '10');

        static::assertEquals(
            [
                'one'   => 'с_one',
                'two'   => 'с_two',
                'three' => 'с_three',
            ],
            $this->update->getRawState('set'),
        );
    }

    #[Test]
    #[CoversNothing]
    public function specificationconstantsCouldBeOverridedByExtensionInGetSqlString(): void
    {
        $this->update = new UpdateIgnore();

        $this->update
            ->table('foo')
            ->set(['bar' => 'baz', 'boo' => new Expression('NOW()'), 'bam' => null])
            ->where('x = y');

        static::assertSame(
            'UPDATE IGNORE "foo" SET "bar" = \'baz\', "boo" = NOW(), "bam" = NULL WHERE x = y',
            $this->update->getSqlString(new TrustingSql92Platform()),
        );

        // with TableIdentifier
        $this->update = new UpdateIgnore();
        $this->update
            ->table(new TableIdentifier('foo', 'sch'))
            ->set(['bar' => 'baz', 'boo' => new Expression('NOW()'), 'bam' => null])
            ->where('x = y');

        static::assertSame(
            'UPDATE IGNORE "sch"."foo" SET "bar" = \'baz\', "boo" = NOW(), "bam" = NULL WHERE x = y',
            $this->update->getSqlString(new TrustingSql92Platform()),
        );
    }

    #[Test]
    #[CoversNothing]
    public function specificationconstantsCouldBeOverridedByExtensionInPrepareStatement(): void
    {
        $updateIgnore = new UpdateIgnore();

        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('getPrepareType')->willReturn('positional');
        $mockDriver->expects($this->any())->method('formatParameterName')->willReturn('?');
        $mockAdapter = $this->createMockAdapter($mockDriver);

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $pContainer    = new ParameterContainer([]);
        $mockStatement->expects($this->any())->method('getParameterContainer')->willReturn($pContainer);

        $mockStatement->expects($this->once())
            ->method('setSql')
            ->with(static::equalTo('UPDATE IGNORE "foo" SET "bar" = ?, "boo" = NOW() WHERE x = y'));

        $updateIgnore->table('foo')
            ->set(['bar' => 'baz', 'boo' => new Expression('NOW()')])
            ->where('x = y');

        $updateIgnore->prepareStatement($mockAdapter, $mockStatement);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function table(): void
    {
        $this->update->table('foo');
        static::assertSame('foo', static::readAttribute($this->update, 'table'));

        $tableIdentifier = new TableIdentifier('foo', 'bar');
        $this->update->table($tableIdentifier);
        static::assertEquals($tableIdentifier, static::readAttribute($this->update, 'table'));
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function where(): void
    {
        $this->update->where('x = y');
        $this->update->where(['foo > ?' => 5]);
        $this->update->where(['id' => 2]);
        $this->update->where(['a = b'], PredicateSet::OP_OR);
        $this->update->where(['c1' => null]);
        $this->update->where(['c2' => [1, 2, 3]]);
        $this->update->where([new IsNotNull('c3')]);

        $where = $this->update->where;

        $predicates = static::readAttribute($where, 'predicates');

        static::assertIsArray($predicates);

        static::assertSame('AND', $predicates[0][0] ?? '');
        static::assertInstanceOf(Literal::class, $predicates[0][1] ?? null);

        static::assertSame('AND', $predicates[1][0] ?? '');
        static::assertInstanceOf(\PhpDb\Sql\Predicate\Expression::class, $predicates[1][1] ?? null);

        static::assertSame('AND', $predicates[2][0] ?? '');
        static::assertInstanceOf(Operator::class, $predicates[2][1] ?? null);

        static::assertSame('OR', $predicates[3][0] ?? '');
        static::assertInstanceOf(Literal::class, $predicates[3][1] ?? null);

        static::assertSame('AND', $predicates[4][0] ?? '');
        static::assertInstanceOf(IsNull::class, $predicates[4][1] ?? null);

        static::assertSame('AND', $predicates[5][0] ?? '');
        static::assertInstanceOf(In::class, $predicates[5][1] ?? null);

        static::assertSame('AND', $predicates[6][0] ?? '');
        static::assertInstanceOf(IsNotNull::class, $predicates[6][1] ?? null);

        $where = new Where();
        $this->update->where($where);
        static::assertSame($where, $this->update->where);

        $this->update->where(static function (Where $what) use ($where): void {
            self::assertSame($where, $what);
        });

        self::expectException(TypeError::class);
        /** @noinspection PhpStrictTypeCheckingInspection */
        $this->update->where(null);
    }

    #[Test]
    #[TestDox('unit test: Test where() accepts Expression (ExpressionInterface) in array')]
    public function whereAcceptsExpressionInterface(): void
    {
        $this->update
            ->table('foo')
            ->set(['bar' => 'baz'])
            ->where([
                new Expression('COUNT(?) > ?', [new Identifier('id'), new Value(5)]),
            ]);

        $where = $this->update->getRawState('where');
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
        $this->update = new Update();
    }
}
