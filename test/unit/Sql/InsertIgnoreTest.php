<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use Override;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\StatementContainer;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\Expression;
use PhpDb\Sql\Insert;
use PhpDb\Sql\InsertIgnore;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;
use PhpDb\Sql\Values;
use PhpDbTest\AdapterTestTrait;
use PhpDbTest\DeprecatedAssertionsTrait;
use PhpDbTest\TestAsset\Replace;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use TypeError;

final class InsertIgnoreTest extends TestCase
{
    use AdapterTestTrait;
    use DeprecatedAssertionsTrait;

    protected InsertIgnore $insert;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->insert = new InsertIgnore();
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
        $values = $this->insert->getRawState('values');
        self::assertInstanceOf(Values::class, $values);
        self::assertEquals($columns, $values->getColumns());
    }

    public function testValues(): void
    {
        $this->insert->values(['foo' => 'bar']);
        $values = $this->insert->getRawState('values');
        self::assertInstanceOf(Values::class, $values);
        self::assertEquals(['foo'], $values->getColumns());
        self::assertEquals(['bar'], $values->getValues());

        // test will merge cols and values of previously set stuff
        $this->insert->values(['foo' => 'bax'], Insert::VALUES_MERGE);
        $this->insert->values(['boom' => 'bam'], Insert::VALUES_MERGE);
        $values = $this->insert->getRawState('values');
        self::assertEquals(['foo', 'boom'], $values->getColumns());
        self::assertEquals(['bax', 'bam'], $values->getValues());

        $this->insert->values(['foo' => 'bax']);
        $values = $this->insert->getRawState('values');
        self::assertEquals(['foo'], $values->getColumns());
        self::assertEquals(['bax'], $values->getValues());
    }

    public function testValuesThrowsExceptionWhenNotArrayOrSelect(): void
    {
        $this->expectException(TypeError::class);
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

    #[Group('Laminas-4926')]
    public function testEmptyArrayValues(): void
    {
        $this->insert->values([]);
        $values = $this->insert->getRawState('values');
        self::assertInstanceOf(Values::class, $values);
        self::assertEquals([], $values->getColumns());
    }

    public function testPrepareStatement(): void
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
            ->with($this->equalTo('INSERT IGNORE INTO "foo" ("bar", "boo") VALUES (?, NOW())'));

        $this->insert->into('foo')
            ->values(['bar' => 'baz', 'boo' => new Expression('NOW()')]);

        $this->insert->prepareStatement($mockAdapter, $mockStatement);

        // with TableIdentifier
        $this->insert = new InsertIgnore();

        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('getPrepareType')->willReturn('positional');
        $mockDriver->expects($this->any())->method('formatParameterName')->willReturn('?');
        $mockAdapter = $this->createMockAdapter($mockDriver);

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $pContainer    = new ParameterContainer([]);
        $mockStatement->expects($this->any())->method('getParameterContainer')->willReturn($pContainer);
        $mockStatement->expects($this->once())
            ->method('setSql')
            ->with($this->equalTo('INSERT IGNORE INTO "sch"."foo" ("bar", "boo") VALUES (?, NOW())'));

        $this->insert->into(new TableIdentifier('foo', 'sch'))
            ->values(['bar' => 'baz', 'boo' => new Expression('NOW()')]);

        $this->insert->prepareStatement($mockAdapter, $mockStatement);
    }

    public function testPrepareStatementWithSelect(): void
    {
        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('getPrepareType')->willReturn('positional');
        $mockDriver->expects($this->any())->method('formatParameterName')->willReturn('?');
        $mockAdapter = $this->createMockAdapter($mockDriver);

        $mockStatement = new StatementContainer();

        $select = new Select('bar');
        $this->insert
                ->into('foo')
                ->columns(['col1'])
                ->select($select->where(['x' => 5]))
                ->prepareStatement($mockAdapter, $mockStatement);

        self::assertEquals(
            'INSERT IGNORE INTO "foo" ("col1") SELECT "bar".* FROM "bar" WHERE "x" = ?',
            $mockStatement->getSql()
        );
        $parameters = $mockStatement->getParameterContainer()->getNamedArray();
        self::assertSame(['where1' => 5], $parameters);
    }

    public function testGetSqlString(): void
    {
        $this->insert->into('foo')
            ->values(['bar' => 'baz', 'boo' => new Expression('NOW()'), 'bam' => null]);

        self::assertEquals(
            'INSERT IGNORE INTO "foo" ("bar", "boo", "bam") VALUES (\'baz\', NOW(), NULL)',
            $this->insert->getSqlString(new TrustingSql92Platform())
        );

        // with TableIdentifier
        $this->insert = new InsertIgnore();
        $this->insert->into(new TableIdentifier('foo', 'sch'))
            ->values(['bar' => 'baz', 'boo' => new Expression('NOW()'), 'bam' => null]);

        self::assertEquals(
            'INSERT IGNORE INTO "sch"."foo" ("bar", "boo", "bam") VALUES (\'baz\', NOW(), NULL)',
            $this->insert->getSqlString(new TrustingSql92Platform())
        );

        // with Select
        $this->insert = new InsertIgnore();
        $select       = new Select();
        $this->insert->into('foo')->select($select->from('bar'));

        self::assertEquals(
            'INSERT IGNORE INTO "foo" SELECT "bar".* FROM "bar"',
            $this->insert->getSqlString(new TrustingSql92Platform())
        );

        // with Select and columns
        $this->insert->columns(['col1', 'col2']);
        self::assertEquals(
            'INSERT IGNORE INTO "foo" ("col1", "col2") SELECT "bar".* FROM "bar"',
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
            'INSERT IGNORE INTO "foo" ("col1", "col2", "col3") VALUES (\'val1\', \'val2\', \'val3\')',
            $this->insert->getSqlString(new TrustingSql92Platform())
        );
    }

    // @codingStandardsIgnoreStart
    public function test__set(): void
    {
        // @codingStandardsIgnoreEnd
        $this->insert->foo = 'bar';
        $values = $this->insert->getRawState('values');
        self::assertInstanceOf(Values::class, $values);
        self::assertEquals(['foo'], $values->getColumns());
        self::assertEquals(['bar'], $values->getValues());
    }

    // @codingStandardsIgnoreStart
    public function test__unset(): void
    {
        // @codingStandardsIgnoreEnd
        $this->insert->foo = 'bar';
        $values = $this->insert->getRawState('values');
        self::assertEquals(['foo'], $values->getColumns());
        self::assertEquals(['bar'], $values->getValues());
        unset($this->insert->foo);
        $values = $this->insert->getRawState('values');
        self::assertEquals([], $values->getColumns());
        self::assertEquals([], $values->getValues());

        $this->insert->foo = null;
        $values = $this->insert->getRawState('values');
        self::assertEquals(['foo'], $values->getColumns());
        self::assertEquals([null], $values->getValues());

        unset($this->insert->foo);
        $values = $this->insert->getRawState('values');
        self::assertEquals([], $values->getColumns());
        self::assertEquals([], $values->getValues());
    }

    // @codingStandardsIgnoreStart
    public function test__isset(): void
    {
        // @codingStandardsIgnoreEnd
        $this->insert->foo = 'bar';
        self::assertTrue(isset($this->insert->foo));

        $this->insert->foo = null;
        self::assertTrue(isset($this->insert->foo));
    }

    // @codingStandardsIgnoreStart
    public function test__get(): void
    {
        // @codingStandardsIgnoreEnd
        $this->insert->foo = 'bar';
        self::assertEquals('bar', $this->insert->foo);

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
            'INSERT IGNORE INTO "foo" ("bar", "boo", "bam", "qux") VALUES (\'baz\', NOW(), NULL, \'100\')',
            $this->insert->getSqlString(new TrustingSql92Platform())
        );
    }

    /**
     * @deprecated SPECIFICATION_* constants no longer exist in new architecture
     */
    #[CoversNothing]
    #[RequiresMethod(InsertIgnore::class, 'processInsert')]
    public function testSpecificationconstantsCouldBeOverridedByExtensionInPrepareStatement(): void
    {
        $replace = new Replace();

        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('getPrepareType')->willReturn('positional');
        $mockDriver->expects($this->any())->method('formatParameterName')->willReturn('?');
        $mockAdapter = $this->createMockAdapter($mockDriver);

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
        $mockAdapter = $this->createMockAdapter($mockDriver);

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

    /**
     * @deprecated SPECIFICATION_* constants no longer exist in new architecture
     */
    #[CoversNothing]
    #[RequiresMethod(InsertIgnore::class, 'processInsert')]
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
