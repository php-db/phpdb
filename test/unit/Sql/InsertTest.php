<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use Override;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\StatementContainer;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\Expression;
use PhpDb\Sql\Insert;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;
use PhpDbTest\AdapterTestTrait;
use PhpDbTest\DeprecatedAssertionsTrait;
use PhpDbTest\TestAsset\Replace;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use TypeError;

use function sprintf;

#[IgnoreDeprecations]
#[RequiresPhp('<= 8.6')]
#[CoversMethod(Insert::class, '__construct')]
#[CoversMethod(Insert::class, 'into')]
#[CoversMethod(Insert::class, 'columns')]
#[CoversMethod(Insert::class, 'values')]
#[CoversMethod(Insert::class, 'select')]
#[CoversMethod(Insert::class, 'getRawState')]
#[CoversMethod(Insert::class, 'processInsert')]
#[CoversMethod(Insert::class, 'processSelect')]
#[CoversMethod(Insert::class, 'prepareStatement')]
#[CoversMethod(Insert::class, 'getSqlString')]
#[CoversMethod(Insert::class, '__set')]
#[CoversMethod(Insert::class, '__unset')]
#[CoversMethod(Insert::class, '__isset')]
#[CoversMethod(Insert::class, '__get')]
final class InsertTest extends TestCase
{
    use AdapterTestTrait;
    use DeprecatedAssertionsTrait;

    protected Insert $insert;

    // @codingStandardsIgnoreStart
    #[Test]
    public function _get(): void
    {
        // @codingStandardsIgnoreEnd
        /** @psalm-suppress UndefinedMagicPropertyAssignment */
        $this->insert->foo = 'bar';
        static::assertSame('bar', $this->insert->foo);

        /** @psalm-suppress UndefinedMagicPropertyAssignment */
        $this->insert->foo = null;
        static::assertNull($this->insert->foo);
    }

    // @codingStandardsIgnoreStart
    #[Test]
    public function _isset(): void
    {
        // @codingStandardsIgnoreEnd
        /** @psalm-suppress UndefinedMagicPropertyAssignment */
        $this->insert->foo = 'bar';
        /** @psalm-suppress RedundantCondition */
        static::assertTrue(isset($this->insert->foo));

        /** @psalm-suppress UndefinedMagicPropertyAssignment */
        $this->insert->foo = null;
        /** @psalm-suppress TypeDoesNotContainType */
        static::assertTrue(isset($this->insert->foo));
    }

    // @codingStandardsIgnoreStart
    #[Test]
    public function _set(): void
    {
        // @codingStandardsIgnoreEnd
        /** @psalm-suppress UndefinedMagicPropertyAssignment */
        $this->insert->foo = 'bar';
        static::assertEquals(['foo'], $this->insert->getRawState('columns'));
        static::assertEquals(['bar'], $this->insert->getRawState('values'));
    }

    // @codingStandardsIgnoreStart
    #[Test]
    public function _unset(): void
    {
        // @codingStandardsIgnoreEnd
        /** @psalm-suppress UndefinedMagicPropertyAssignment */
        $this->insert->foo = 'bar';
        static::assertEquals(['foo'], $this->insert->getRawState('columns'));
        static::assertEquals(['bar'], $this->insert->getRawState('values'));
        unset($this->insert->foo);
        static::assertEquals([], $this->insert->getRawState('columns'));
        static::assertEquals([], $this->insert->getRawState('values'));

        /** @psalm-suppress UndefinedMagicPropertyAssignment */
        $this->insert->foo = null;
        static::assertEquals(['foo'], $this->insert->getRawState('columns'));
        static::assertEquals([null], $this->insert->getRawState('values'));

        unset($this->insert->foo);
        static::assertEquals([], $this->insert->getRawState('columns'));
        static::assertEquals([], $this->insert->getRawState('values'));
    }

    #[Test]
    public function columns(): void
    {
        $columns = ['foo', 'bar'];
        $this->insert->columns($columns);
        static::assertEquals($columns, $this->insert->getRawState('columns'));
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    #[Group('Laminas-4926')]
    public function emptyArrayValues(): void
    {
        $this->insert->values([]);
        static::assertEquals([], static::readAttribute($this->insert, 'columns'));
    }

    #[Test]
    public function getSqlString(): void
    {
        $this->insert->into('foo')->values(['bar' => 'baz', 'boo' => new Expression('NOW()'), 'bam' => null]);

        static::assertSame(
            'INSERT INTO "foo" ("bar", "boo", "bam") VALUES (\'baz\', NOW(), NULL)',
            $this->insert->getSqlString(new TrustingSql92Platform()),
        );

        // with TableIdentifier
        $this->insert = new Insert();
        $this->insert
            ->into(new TableIdentifier('foo', 'sch'))
            ->values(['bar' => 'baz', 'boo' => new Expression('NOW()'), 'bam' => null]);

        static::assertSame(
            'INSERT INTO "sch"."foo" ("bar", "boo", "bam") VALUES (\'baz\', NOW(), NULL)',
            $this->insert->getSqlString(new TrustingSql92Platform()),
        );

        // with Select
        $this->insert = new Insert();
        $select       = new Select();
        $this->insert->into('foo')->select($select->from('bar'));

        static::assertSame(
            'INSERT INTO "foo"  SELECT "bar".* FROM "bar"',
            $this->insert->getSqlString(new TrustingSql92Platform()),
        );

        // with Select and columns
        $this->insert->columns(['col1', 'col2']);
        static::assertSame(
            'INSERT INTO "foo" ("col1", "col2") SELECT "bar".* FROM "bar"',
            $this->insert->getSqlString(new TrustingSql92Platform()),
        );
    }

    #[Test]
    public function getSqlStringThrowsExceptionWhenNoValuesOrSelect(): void
    {
        $this->insert->into('foo');

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::MISSING_VALUES_OR_SELECT);
        $this->insert->getSqlString(new TrustingSql92Platform());
    }

    #[Test]
    public function getSqlStringUsingColumnsAndValuesMethods(): void
    {
        // With columns() and values()
        $this->insert
            ->into('foo')
            ->columns(['col1', 'col2', 'col3'])
            ->values(['val1', 'val2', 'val3']);
        static::assertSame(
            'INSERT INTO "foo" ("col1", "col2", "col3") VALUES (\'val1\', \'val2\', \'val3\')',
            $this->insert->getSqlString(new TrustingSql92Platform()),
        );
    }

    #[Test]
    public function getThrowsExceptionForNonExistentColumn(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(sprintf(InvalidArgumentException::UNKNOWN_COLUMN_KEY, 'nonexistent'));
        $value = $this->insert->nonexistent;
    }

    #[Test]
    public function into(): void
    {
        $this->insert->into('table');
        static::assertSame('table', $this->insert->getRawState('table'));

        $tableIdentifier = new TableIdentifier('table', 'schema');
        $this->insert->into($tableIdentifier);
        static::assertEquals($tableIdentifier, $this->insert->getRawState('table'));
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
            ->with(static::equalTo('INSERT INTO "foo" ("bar", "boo") VALUES (?, NOW())'));

        $this->insert->into('foo')->values(['bar' => 'baz', 'boo' => new Expression('NOW()')]);

        $this->insert->prepareStatement($mockAdapter, $mockStatement);

        // with TableIdentifier
        $this->insert = new Insert();

        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('getPrepareType')->willReturn('positional');
        $mockDriver->expects($this->any())->method('formatParameterName')->willReturn('?');
        $mockAdapter = $this->createMockAdapter($mockDriver);

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $pContainer    = new ParameterContainer([]);
        $mockStatement->expects($this->any())->method('getParameterContainer')->willReturn($pContainer);
        $mockStatement->expects($this->once())
            ->method('setSql')
            ->with(static::equalTo('INSERT INTO "sch"."foo" ("bar", "boo") VALUES (?, NOW())'));

        $this->insert
            ->into(new TableIdentifier('foo', 'sch'))
            ->values(['bar' => 'baz', 'boo' => new Expression('NOW()')]);

        $this->insert->prepareStatement($mockAdapter, $mockStatement);
    }

    #[Test]
    public function prepareStatementCreatesParameterContainerWhenNotPresent(): void
    {
        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('getPrepareType')->willReturn('positional');
        $mockDriver->expects($this->any())->method('formatParameterName')->willReturn('?');
        $mockAdapter = $this->createMockAdapter($mockDriver);

        $mockStatement = $this->getMockBuilder(StatementInterface::class)->getMock();
        $mockStatement->expects($this->once())
            ->method('getParameterContainer')
            ->willReturn(null);
        $mockStatement->expects($this->once())
            ->method('setParameterContainer')
            ->with(static::isInstanceOf(ParameterContainer::class))
            ->willReturnSelf();
        $mockStatement->expects($this->once())
            ->method('setSql')
            ->with(static::stringContains('INSERT INTO'))
            ->willReturnSelf();

        $this->insert->into('foo')->values(['bar' => 'baz']);

        $result = $this->insert->prepareStatement($mockAdapter, $mockStatement);

        static::assertSame($mockStatement, $result);
    }

    #[Test]
    public function prepareStatementWithSelect(): void
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

        static::assertSame(
            'INSERT INTO "foo" ("col1") SELECT "bar".* FROM "bar" WHERE "x" = ?',
            $mockStatement->getSql(),
        );
        $parameters = $mockStatement->getParameterContainer();
        static::assertInstanceOf(ParameterContainer::class, $parameters);

        $namedArray = $parameters->getNamedArray();
        static::assertSame(['subselect1where1' => 5], $namedArray);
    }

    #[Test]
    public function processInsertWithPdoDriverUsesDriverColumnQuoting(): void
    {
        $mockDriver = $this->getMockBuilder(PdoDriverInterface::class)->getMock();
        $mockDriver->expects($this->any())->method('getPrepareType')->willReturn('positional');
        $mockDriver->expects($this->any())
            ->method('formatParameterName')
            ->willReturnCallback(static fn(string $name): string => ":{$name}");
        $mockAdapter = $this->createMockAdapter($mockDriver);

        $mockStatement = new StatementContainer();

        $this->insert->into('foo')->values(['bar' => 'baz', 'boo' => 'qux']);

        $this->insert->prepareStatement($mockAdapter, $mockStatement);

        $sql = $mockStatement->getSql();
        static::assertStringContainsString(':c_0', $sql);
        static::assertStringContainsString(':c_1', $sql);
    }

    #[Test]
    #[CoversNothing]
    public function specificationconstantsCouldBeOverridedByExtensionInGetSqlString(): void
    {
        $replace = new Replace();
        $replace->into('foo')
            ->values(['bar' => 'baz', 'boo' => new Expression('NOW()'), 'bam' => null]);

        static::assertSame(
            'REPLACE INTO "foo" ("bar", "boo", "bam") VALUES (\'baz\', NOW(), NULL)',
            $replace->getSqlString(new TrustingSql92Platform()),
        );

        // with TableIdentifier
        $replace = new Replace();
        $replace->into(new TableIdentifier('foo', 'sch'))
            ->values(['bar' => 'baz', 'boo' => new Expression('NOW()'), 'bam' => null]);

        static::assertSame(
            'REPLACE INTO "sch"."foo" ("bar", "boo", "bam") VALUES (\'baz\', NOW(), NULL)',
            $replace->getSqlString(new TrustingSql92Platform()),
        );
    }

    #[Test]
    #[CoversNothing]
    public function specificationconstantsCouldBeOverridedByExtensionInPrepareStatement(): void
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
            ->with(static::equalTo('REPLACE INTO "foo" ("bar", "boo") VALUES (?, NOW())'));

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
            ->with(static::equalTo('REPLACE INTO "sch"."foo" ("bar", "boo") VALUES (?, NOW())'));

        $replace->into(new TableIdentifier('foo', 'sch'))
            ->values(['bar' => 'baz', 'boo' => new Expression('NOW()')]);

        $replace->prepareStatement($mockAdapter, $mockStatement);
    }

    #[Test]
    public function unsetThrowsExceptionForNonExistentColumn(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(sprintf(InvalidArgumentException::UNKNOWN_COLUMN_KEY, 'nonexistent'));
        unset($this->insert->nonexistent);
    }

    #[Test]
    public function values(): void
    {
        $this->insert->values(['foo' => 'bar']);
        static::assertEquals(['foo'], $this->insert->getRawState('columns'));
        static::assertEquals(['bar'], $this->insert->getRawState('values'));

        // test will merge cols and values of previously set stuff
        $this->insert->values(['foo' => 'bax'], Insert::VALUES_MERGE);
        $this->insert->values(['boom' => 'bam'], Insert::VALUES_MERGE);
        static::assertEquals(['foo', 'boom'], $this->insert->getRawState('columns'));
        static::assertEquals(['bax', 'bam'], $this->insert->getRawState('values'));

        $this->insert->values(['foo' => 'bax']);
        static::assertEquals(['foo'], $this->insert->getRawState('columns'));
        static::assertEquals(['bax'], $this->insert->getRawState('values'));
    }

    #[Test]
    #[Group('Laminas-536')]
    public function valuesMerge(): void
    {
        $this->insert->into('foo')->values(['bar' => 'baz', 'boo' => new Expression('NOW()'), 'bam' => null]);
        $this->insert->into('foo')
            ->values(['qux' => 100], Insert::VALUES_MERGE);

        static::assertSame(
            'INSERT INTO "foo" ("bar", "boo", "bam", "qux") VALUES (\'baz\', NOW(), NULL, \'100\')',
            $this->insert->getSqlString(new TrustingSql92Platform()),
        );
    }

    #[Test]
    public function valuesThrowsExceptionWhenArrayMergeOverSelect(): void
    {
        $this->insert->values(new Select());

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(
            'An array of values cannot be provided with the merge flag when a PhpDb\Sql\Select instance already '
                . 'exists as the value source',
        );
        $this->insert->values(['foo' => 'bar'], Insert::VALUES_MERGE);
    }

    #[Test]
    public function valuesThrowsExceptionWhenNotArrayOrSelect(): void
    {
        self::expectException(TypeError::class);
        /** @psalm-suppress InvalidArgument */
        $this->insert->values(5);
    }

    #[Test]
    public function valuesThrowsExceptionWhenSelectMergeOverArray(): void
    {
        $this->insert->values(['foo' => 'bar']);

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::SELECT_WITH_MERGE_FLAG);
        $this->insert->values(new Select(), Insert::VALUES_MERGE);
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->insert = new Insert();
    }
}
