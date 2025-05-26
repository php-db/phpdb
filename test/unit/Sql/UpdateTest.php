<?php

namespace LaminasTest\Db\Sql;

use Laminas\Db\Adapter\AbstractAdapter as Adapter;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Adapter\ParameterContainer;
use Laminas\Db\Sql\Exception\InvalidArgumentException;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Join;
use Laminas\Db\Sql\Predicate\In;
use Laminas\Db\Sql\Predicate\IsNotNull;
use Laminas\Db\Sql\Predicate\IsNull;
use Laminas\Db\Sql\Predicate\Literal;
use Laminas\Db\Sql\Predicate\Operator;
use Laminas\Db\Sql\Predicate\PredicateSet;
use Laminas\Db\Sql\TableIdentifier;
use Laminas\Db\Sql\Update;
use Laminas\Db\Sql\Where;
use LaminasTest\Db\DeprecatedAssertionsTrait;
use LaminasTest\Db\TestAsset\TrustingSql92Platform;
use LaminasTest\Db\TestAsset\UpdateIgnore;
use Override;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use ReflectionException;

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
final class UpdateTest extends TestCase
{
    use DeprecatedAssertionsTrait;

    protected Update $update;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->update = new Update();
    }

    /**
     * @throws ReflectionException
     */
    public function testTable(): void
    {
        $this->update->table('foo');
        self::assertEquals('foo', $this->readAttribute($this->update, 'table'));

        $tableIdentifier = new TableIdentifier('foo', 'bar');
        $this->update->table($tableIdentifier);
        self::assertEquals($tableIdentifier, $this->readAttribute($this->update, 'table'));
    }

    /**
     * @throws ReflectionException
     */
    public function testConstruct(): void
    {
        $update = new Update('foo');
        self::assertEquals('foo', $this->readAttribute($update, 'table'));
    }

    public function testSet(): void
    {
        $this->update->set(['foo' => 'bar']);
        self::assertEquals(['foo' => 'bar'], $this->update->getRawState('set'));
    }

    public function testSortableSet(): void
    {
        $this->update->set([
            'two'   => 'с_two',
            'three' => 'с_three',
        ]);
        $this->update->set(['one' => 'с_one'], '10');

        self::assertEquals(
            [
                'one'   => 'с_one',
                'two'   => 'с_two',
                'three' => 'с_three',
            ],
            $this->update->getRawState('set')
        );
    }

    /**
     * @throws ReflectionException
     */
    public function testWhere(): void
    {
        $this->update->where('x = y');
        $this->update->where(['foo > ?' => 5]);
        $this->update->where(['id' => 2]);
        $this->update->where(['a = b'], PredicateSet::OP_OR);
        $this->update->where(['c1' => null]);
        $this->update->where(['c2' => [1, 2, 3]]);
        $this->update->where([new IsNotNull('c3')]);
        $where = $this->update->where;

        $predicates = $this->readAttribute($where, 'predicates');

        self::assertIsArray($predicates);

        self::assertEquals('AND', $predicates[0][0] ?? '');
        self::assertInstanceOf(Literal::class, $predicates[0][1] ?? null);

        self::assertEquals('AND', $predicates[1][0] ?? '');
        self::assertInstanceOf(\Laminas\Db\Sql\Predicate\Expression::class, $predicates[1][1] ?? null);

        self::assertEquals('AND', $predicates[2][0] ?? '');
        self::assertInstanceOf(Operator::class, $predicates[2][1] ?? null);

        self::assertEquals('OR', $predicates[3][0] ?? '');
        self::assertInstanceOf(Literal::class, $predicates[3][1] ?? null);

        self::assertEquals('AND', $predicates[4][0] ?? '');
        self::assertInstanceOf(IsNull::class, $predicates[4][1] ?? null);

        self::assertEquals('AND', $predicates[5][0] ?? '');
        self::assertInstanceOf(In::class, $predicates[5][1] ?? null);

        self::assertEquals('AND', $predicates[6][0] ?? '');
        self::assertInstanceOf(IsNotNull::class, $predicates[6][1] ?? null);

        $where = new Where();
        $this->update->where($where);
        self::assertSame($where, $this->update->where);

        $this->update->where(function (Where $what) use ($where): void {
            self::assertSame($where, $what);
        });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Predicate cannot be null');
        /** @psalm-suppress NullArgument - Ensure exception is thrown */
        $this->update->where(null);
    }

    #[Group('Laminas-240')]
    public function testPassingMultipleKeyValueInWhereClause(): void
    {
        $update = clone $this->update;
        $update->table('table');
        $update->set(['fld1' => 'val1']);
        $update->where(['id1' => 'val1', 'id2' => 'val2']);
        self::assertEquals(
            'UPDATE "table" SET "fld1" = \'val1\' WHERE "id1" = \'val1\' AND "id2" = \'val2\'',
            $update->getSqlString(new TrustingSql92Platform())
        );
    }

    public function testGetRawState(): void
    {
        $this->update->table('foo')
            ->set(['bar' => 'baz'])
            ->where('x = y');

        self::assertEquals('foo', $this->update->getRawState('table'));
        self::assertTrue($this->update->getRawState('emptyWhereProtection'));
        self::assertEquals(['bar' => 'baz'], $this->update->getRawState('set'));
        self::assertInstanceOf(Where::class, $this->update->getRawState('where'));
    }

    public function testPrepareStatement(): void
    {
        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('getPrepareType')->willReturn('positional');
        $mockDriver->expects($this->any())->method('formatParameterName')->willReturn('?');
        $mockAdapter = $this->getMockBuilder(Adapter::class)
            ->onlyMethods([])
            ->setConstructorArgs([$mockDriver])
            ->getMock();

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $pContainer    = new ParameterContainer([]);
        $mockStatement->expects($this->any())->method('getParameterContainer')->willReturn($pContainer);

        $mockStatement->expects($this->once())
            ->method('setSql')
            ->with($this->equalTo('UPDATE "foo" SET "bar" = ?, "boo" = NOW() WHERE x = y'));

        $this->update->table('foo')
            ->set(['bar' => 'baz', 'boo' => new Expression('NOW()')])
            ->where('x = y');

        $this->update->prepareStatement($mockAdapter, $mockStatement);

        // with TableIdentifier
        $this->update = new Update();
        $mockDriver   = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('getPrepareType')->willReturn('positional');
        $mockDriver->expects($this->any())->method('formatParameterName')->willReturn('?');
        $mockAdapter = $this->getMockBuilder(Adapter::class)
            ->onlyMethods([])
            ->setConstructorArgs([$mockDriver])
            ->getMock();

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $pContainer    = new ParameterContainer([]);
        $mockStatement->expects($this->any())->method('getParameterContainer')->willReturn($pContainer);

        $mockStatement->expects($this->once())
            ->method('setSql')
            ->with($this->equalTo('UPDATE "sch"."foo" SET "bar" = ?, "boo" = NOW() WHERE x = y'));

        $this->update->table(new TableIdentifier('foo', 'sch'))
            ->set(['bar' => 'baz', 'boo' => new Expression('NOW()')])
            ->where('x = y');

        $this->update->prepareStatement($mockAdapter, $mockStatement);
    }

    public function testGetSqlString(): void
    {
        $this->update->table('foo')
            ->set(['bar' => 'baz', 'boo' => new Expression('NOW()'), 'bam' => null])
            ->where('x = y');

        self::assertEquals(
            'UPDATE "foo" SET "bar" = \'baz\', "boo" = NOW(), "bam" = NULL WHERE x = y',
            $this->update->getSqlString(new TrustingSql92Platform())
        );

        // with TableIdentifier
        $this->update = new Update();
        $this->update->table(new TableIdentifier('foo', 'sch'))
            ->set(['bar' => 'baz', 'boo' => new Expression('NOW()'), 'bam' => null])
            ->where('x = y');

        self::assertEquals(
            'UPDATE "sch"."foo" SET "bar" = \'baz\', "boo" = NOW(), "bam" = NULL WHERE x = y',
            $this->update->getSqlString(new TrustingSql92Platform())
        );
    }

    #[Group('6768')]
    #[Group('6773')]
    public function testGetSqlStringForFalseUpdateValueParameter(): void
    {
        $this->update = new Update();
        $this->update->table(new TableIdentifier('foo', 'sch'))
            ->set(['bar' => false, 'boo' => 'test', 'bam' => true])
            ->where('x = y');
        self::assertEquals(
            'UPDATE "sch"."foo" SET "bar" = \'\', "boo" = \'test\', "bam" = \'1\' WHERE x = y',
            $this->update->getSqlString(new TrustingSql92Platform())
        );
    }

    public function testGetUpdate(): void
    {
        $getWhere = $this->update->__get('where');
        self::assertInstanceOf(Where::class, $getWhere);
    }

    public function testGetUpdateFails(): void
    {
        /** @psalm-suppress UndefinedThisPropertyFetch - Ensure non-existent property returns null */
        $getWhat = $this->update->__get('what');
        self::assertNull($getWhat);
    }

    public function testCloneUpdate(): void
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
        self::assertEquals(
            'UPDATE "foo" SET "bar" = \'baz\' WHERE id = \'1\'',
            $update2->getSqlString(new TrustingSql92Platform())
        );
    }

    #[CoversNothing]
    public function testSpecificationconstantsCouldBeOverridedByExtensionInPrepareStatement(): void
    {
        $updateIgnore = new UpdateIgnore();

        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('getPrepareType')->willReturn('positional');
        $mockDriver->expects($this->any())->method('formatParameterName')->willReturn('?');
        $mockAdapter = $this->getMockBuilder(Adapter::class)
            ->onlyMethods([])
            ->setConstructorArgs([$mockDriver])
            ->getMock();

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $pContainer    = new ParameterContainer([]);
        $mockStatement->expects($this->any())->method('getParameterContainer')->willReturn($pContainer);

        $mockStatement->expects($this->once())
            ->method('setSql')
            ->with($this->equalTo('UPDATE IGNORE "foo" SET "bar" = ?, "boo" = NOW() WHERE x = y'));

        $updateIgnore->table('foo')
            ->set(['bar' => 'baz', 'boo' => new Expression('NOW()')])
            ->where('x = y');

        $updateIgnore->prepareStatement($mockAdapter, $mockStatement);
    }

    #[CoversNothing]
    public function testSpecificationconstantsCouldBeOverridedByExtensionInGetSqlString(): void
    {
        $this->update = new UpdateIgnore();

        $this->update->table('foo')
            ->set(['bar' => 'baz', 'boo' => new Expression('NOW()'), 'bam' => null])
            ->where('x = y');

        self::assertEquals(
            'UPDATE IGNORE "foo" SET "bar" = \'baz\', "boo" = NOW(), "bam" = NULL WHERE x = y',
            $this->update->getSqlString(new TrustingSql92Platform())
        );

        // with TableIdentifier
        $this->update = new UpdateIgnore();
        $this->update->table(new TableIdentifier('foo', 'sch'))
            ->set(['bar' => 'baz', 'boo' => new Expression('NOW()'), 'bam' => null])
            ->where('x = y');

        self::assertEquals(
            'UPDATE IGNORE "sch"."foo" SET "bar" = \'baz\', "boo" = NOW(), "bam" = NULL WHERE x = y',
            $this->update->getSqlString(new TrustingSql92Platform())
        );
    }

    public function testJoin(): void
    {
        $this->update->table('Document');
        $this->update->set(['x' => 'y'])
            ->join(
                'User', // table name
                'User.UserId = Document.UserId' // expression to join on
                // default JOIN INNER
            )
            ->join(
                'Category',
                'Category.CategoryId = Document.CategoryId',
                Join::JOIN_LEFT // (optional), one of inner, outer, left, right
            );

        self::assertEquals(
            'UPDATE "Document" INNER JOIN "User" ON "User"."UserId" = "Document"."UserId" '
                . 'LEFT JOIN "Category" ON "Category"."CategoryId" = "Document"."CategoryId" SET "x" = \'y\'',
            $this->update->getSqlString(new TrustingSql92Platform())
        );
    }

    /**
     * Here test if we want update fields from specific table.
     * Important when we're updating fields that are existing in several tables in one query.
     * The same test as above but here we will specify table in update params
     */
    public function testJoinMultiUpdate(): void
    {
        $this->update->table('Document');
        $this->update->set(['Documents.x' => 'y'])
            ->join(
                'User',
                'User.UserId = Document.UserId'
            )
            ->join(
                'Category',
                'Category.CategoryId = Document.CategoryId',
                Join::JOIN_LEFT
            );

        self::assertEquals(
            'UPDATE "Document" INNER JOIN "User" ON "User"."UserId" = "Document"."UserId" '
                . 'LEFT JOIN "Category" ON "Category"."CategoryId" = "Document"."CategoryId" SET "Documents"."x" = \'y\'',
            $this->update->getSqlString(new TrustingSql92Platform())
        );
    }

    #[TestDox('unit test: Test join() returns Update object (is chainable)')]
    public function testJoinChainable(): void
    {
        $return = $this->update->join('baz', 'foo.fooId = baz.fooId', Join::JOIN_LEFT);
        self::assertSame($this->update, $return);
    }
}
