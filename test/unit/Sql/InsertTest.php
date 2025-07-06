<?php

namespace PhpDbTest\Sql;

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\StatementContainer;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\Expression;
use PhpDb\Sql\Insert;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;
use PhpDbTest\DeprecatedAssertionsTrait;
use PhpDbTest\TestAsset\Replace;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use Override;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionException;

#[CoversMethod(Insert::class, 'into')]
#[CoversMethod(Insert::class, 'columns')]
#[CoversMethod(Insert::class, 'values')]
#[CoversMethod(Insert::class, 'prepareStatement')]
#[CoversMethod(Insert::class, 'getSqlString')]
#[CoversMethod(Insert::class, '__set')]
#[CoversMethod(Insert::class, '__unset')]
#[CoversMethod(Insert::class, '__isset')]
#[CoversMethod(Insert::class, '__get')]
final class InsertTest extends TestCase
{
    use DeprecatedAssertionsTrait;

    protected Insert $insert;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->insert = new Insert();
    }

    public function testInto(): void
    {
        $this->insert->into('table');
        self::assertEquals('table', $this->insert->getRawState('table'));

        $tableIdentifier = new TableIdentifier('table', 'schema');
        $this->insert->into($tableIdentifier);
        self::assertEquals($tableIdentifier, $this->insert->getRawState('table'));
    }

    public function testColumns(): void
    {
        $columns = ['foo', 'bar'];
        $this->insert->columns($columns);
        self::assertEquals($columns, $this->insert->getRawState('columns'));
    }

    public function testValues(): void
    {
        $this->insert->values(['foo' => 'bar']);
        self::assertEquals(['foo'], $this->insert->getRawState('columns'));
        self::assertEquals(['bar'], $this->insert->getRawState('values'));

        // test will merge cols and values of previously set stuff
        $this->insert->values(['foo' => 'bax'], Insert::VALUES_MERGE);
        $this->insert->values(['boom' => 'bam'], Insert::VALUES_MERGE);
        self::assertEquals(['foo', 'boom'], $this->insert->getRawState('columns'));
        self::assertEquals(['bax', 'bam'], $this->insert->getRawState('values'));

        $this->insert->values(['foo' => 'bax']);
        self::assertEquals(['foo'], $this->insert->getRawState('columns'));
        self::assertEquals(['bax'], $this->insert->getRawState('values'));
    }

    public function testValuesThrowsExceptionWhenNotArrayOrSelect(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('values() expects an array of values or PhpDb\Sql\Select instance');
        /** @psalm-suppress InvalidArgument */
        $this->insert->values(5);
    }

    public function testValuesThrowsExceptionWhenSelectMergeOverArray(): void
    {
        $this->insert->values(['foo' => 'bar']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A PhpDb\Sql\Select instance cannot be provided with the merge flag');
        $this->insert->values(new Select(), Insert::VALUES_MERGE);
    }

    public function testValuesThrowsExceptionWhenArrayMergeOverSelect(): void
    {
        $this->insert->values(new Select());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'An array of values cannot be provided with the merge flag when a PhpDb\Sql\Select instance already '
            . 'exists as the value source'
        );
        $this->insert->values(['foo' => 'bar'], Insert::VALUES_MERGE);
    }

    /**
     * @throws ReflectionException
     */
    #[Group('Laminas-4926')]
    public function testEmptyArrayValues(): void
    {
        $this->insert->values([]);
        self::assertEquals([], $this->readAttribute($this->insert, 'columns'));
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
            ->with($this->equalTo('INSERT INTO "foo" ("bar", "boo") VALUES (?, NOW())'));

        $this->insert->into('foo')
            ->values(['bar' => 'baz', 'boo' => new Expression('NOW()')]);

        $this->insert->prepareStatement($mockAdapter, $mockStatement);

        // with TableIdentifier
        $this->insert = new Insert();
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
            ->with($this->equalTo('INSERT INTO "sch"."foo" ("bar", "boo") VALUES (?, NOW())'));

        $this->insert->into(new TableIdentifier('foo', 'sch'))
            ->values(['bar' => 'baz', 'boo' => new Expression('NOW()')]);

        $this->insert->prepareStatement($mockAdapter, $mockStatement);
    }

    public function testPrepareStatementWithSelect(): void
    {
        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('getPrepareType')->willReturn('positional');
        $mockDriver->expects($this->any())->method('formatParameterName')->willReturn('?');
        $mockAdapter = $this->getMockBuilder(Adapter::class)
            ->onlyMethods([])
            ->setConstructorArgs([$mockDriver])
            ->getMock();

        $mockStatement = new StatementContainer();

        $select = new Select('bar');
        $this->insert
                ->into('foo')
                ->columns(['col1'])
                ->select($select->where(['x' => 5]))
                ->prepareStatement($mockAdapter, $mockStatement);

        self::assertEquals(
            'INSERT INTO "foo" ("col1") SELECT "bar".* FROM "bar" WHERE "x" = ?',
            $mockStatement->getSql()
        );
        $parameters = $mockStatement->getParameterContainer();
        $this->assertInstanceOf(ParameterContainer::class, $parameters);

        $namedArray = $parameters->getNamedArray();
        self::assertSame(['subselect1where1' => 5], $namedArray);
    }

    public function testGetSqlString(): void
    {
        $this->insert->into('foo')
            ->values(['bar' => 'baz', 'boo' => new Expression('NOW()'), 'bam' => null]);

        self::assertEquals(
            'INSERT INTO "foo" ("bar", "boo", "bam") VALUES (\'baz\', NOW(), NULL)',
            $this->insert->getSqlString(new TrustingSql92Platform())
        );

        // with TableIdentifier
        $this->insert = new Insert();
        $this->insert->into(new TableIdentifier('foo', 'sch'))
            ->values(['bar' => 'baz', 'boo' => new Expression('NOW()'), 'bam' => null]);

        self::assertEquals(
            'INSERT INTO "sch"."foo" ("bar", "boo", "bam") VALUES (\'baz\', NOW(), NULL)',
            $this->insert->getSqlString(new TrustingSql92Platform())
        );

        // with Select
        $this->insert = new Insert();
        $select       = new Select();
        $this->insert->into('foo')->select($select->from('bar'));

        self::assertEquals(
            'INSERT INTO "foo"  SELECT "bar".* FROM "bar"',
            $this->insert->getSqlString(new TrustingSql92Platform())
        );

        // with Select and columns
        $this->insert->columns(['col1', 'col2']);
        self::assertEquals(
            'INSERT INTO "foo" ("col1", "col2") SELECT "bar".* FROM "bar"',
            $this->insert->getSqlString(new TrustingSql92Platform())
        );
    }

    public function testGetSqlStringUsingColumnsAndValuesMethods(): void
    {
        // With columns() and values()
        $this->insert
            ->into('foo')
            ->columns(['col1', 'col2', 'col3'])
            ->values(['val1', 'val2', 'val3']);
        self::assertEquals(
            'INSERT INTO "foo" ("col1", "col2", "col3") VALUES (\'val1\', \'val2\', \'val3\')',
            $this->insert->getSqlString(new TrustingSql92Platform())
        );
    }

    // @codingStandardsIgnoreStart
    public function test__set(): void
    {
        // @codingStandardsIgnoreEnd
        /** @psalm-suppress UndefinedMagicPropertyAssignment */
        $this->insert->foo = 'bar';
        self::assertEquals(['foo'], $this->insert->getRawState('columns'));
        self::assertEquals(['bar'], $this->insert->getRawState('values'));
    }

    // @codingStandardsIgnoreStart
    public function test__unset(): void
    {
        // @codingStandardsIgnoreEnd
        /** @psalm-suppress UndefinedMagicPropertyAssignment */
        $this->insert->foo = 'bar';
        self::assertEquals(['foo'], $this->insert->getRawState('columns'));
        self::assertEquals(['bar'], $this->insert->getRawState('values'));
        unset($this->insert->foo);
        self::assertEquals([], $this->insert->getRawState('columns'));
        self::assertEquals([], $this->insert->getRawState('values'));

        /** @psalm-suppress UndefinedMagicPropertyAssignment */
        $this->insert->foo = null;
        self::assertEquals(['foo'], $this->insert->getRawState('columns'));
        self::assertEquals([null], $this->insert->getRawState('values'));

        unset($this->insert->foo);
        self::assertEquals([], $this->insert->getRawState('columns'));
        self::assertEquals([], $this->insert->getRawState('values'));
    }

    // @codingStandardsIgnoreStart
    public function test__isset(): void
    {
        // @codingStandardsIgnoreEnd
        /** @psalm-suppress UndefinedMagicPropertyAssignment */
        $this->insert->foo = 'bar';
        /** @psalm-suppress RedundantCondition */
        self::assertTrue(isset($this->insert->foo));

        /** @psalm-suppress UndefinedMagicPropertyAssignment */
        $this->insert->foo = null;
        /** @psalm-suppress TypeDoesNotContainType */
        self::assertTrue(isset($this->insert->foo));
    }

    // @codingStandardsIgnoreStart
    public function test__get(): void
    {
        // @codingStandardsIgnoreEnd
        /** @psalm-suppress UndefinedMagicPropertyAssignment */
        $this->insert->foo = 'bar';
        self::assertEquals('bar', $this->insert->foo);

        /** @psalm-suppress UndefinedMagicPropertyAssignment */
        $this->insert->foo = null;
        self::assertNull($this->insert->foo);
    }

    #[Group('Laminas-536')]
    public function testValuesMerge(): void
    {
        $this->insert->into('foo')
            ->values(['bar' => 'baz', 'boo' => new Expression('NOW()'), 'bam' => null]);
        $this->insert->into('foo')
            ->values(['qux' => 100], Insert::VALUES_MERGE);

        self::assertEquals(
            'INSERT INTO "foo" ("bar", "boo", "bam", "qux") VALUES (\'baz\', NOW(), NULL, \'100\')',
            $this->insert->getSqlString(new TrustingSql92Platform())
        );
    }

    #[CoversNothing]
    public function testSpecificationconstantsCouldBeOverridedByExtensionInPrepareStatement(): void
    {
        $replace = new Replace();

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
            ->with($this->equalTo('REPLACE INTO "foo" ("bar", "boo") VALUES (?, NOW())'));

        $replace->into('foo')
            ->values(['bar' => 'baz', 'boo' => new Expression('NOW()')]);

        $replace->prepareStatement($mockAdapter, $mockStatement);

        // with TableIdentifier
        $replace = new Replace();

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
            ->with($this->equalTo('REPLACE INTO "sch"."foo" ("bar", "boo") VALUES (?, NOW())'));

        $replace->into(new TableIdentifier('foo', 'sch'))
            ->values(['bar' => 'baz', 'boo' => new Expression('NOW()')]);

        $replace->prepareStatement($mockAdapter, $mockStatement);
    }

    #[CoversNothing]
    public function testSpecificationconstantsCouldBeOverridedByExtensionInGetSqlString(): void
    {
        $replace = new Replace();
        $replace->into('foo')
            ->values(['bar' => 'baz', 'boo' => new Expression('NOW()'), 'bam' => null]);

        self::assertEquals(
            'REPLACE INTO "foo" ("bar", "boo", "bam") VALUES (\'baz\', NOW(), NULL)',
            $replace->getSqlString(new TrustingSql92Platform())
        );

        // with TableIdentifier
        $replace = new Replace();
        $replace->into(new TableIdentifier('foo', 'sch'))
            ->values(['bar' => 'baz', 'boo' => new Expression('NOW()'), 'bam' => null]);

        self::assertEquals(
            'REPLACE INTO "sch"."foo" ("bar", "boo", "bam") VALUES (\'baz\', NOW(), NULL)',
            $replace->getSqlString(new TrustingSql92Platform())
        );
    }
}
