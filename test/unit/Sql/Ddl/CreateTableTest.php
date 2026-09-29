<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl;

use PhpDb\Sql\Ddl\Column\Column;
use PhpDb\Sql\Ddl\Column\ColumnInterface;
use PhpDb\Sql\Ddl\Constraint;
use PhpDb\Sql\Ddl\Constraint\ConstraintInterface;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Literal;
use PhpDb\Sql\TableIdentifier;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(CreateTable::class, '__construct')]
#[CoversMethod(CreateTable::class, 'ifNotExists')]
#[CoversMethod(CreateTable::class, 'getIfNotExists')]
#[CoversMethod(CreateTable::class, 'setTemporary')]
#[CoversMethod(CreateTable::class, 'isTemporary')]
#[CoversMethod(CreateTable::class, 'setTable')]
#[CoversMethod(CreateTable::class, 'getRawState')]
#[CoversMethod(CreateTable::class, 'addColumn')]
#[CoversMethod(CreateTable::class, 'addConstraint')]
#[CoversMethod(CreateTable::class, 'getSqlString')]
#[CoversMethod(CreateTable::class, 'processTable')]
#[CoversMethod(CreateTable::class, 'processColumns')]
#[CoversMethod(CreateTable::class, 'processCombinedby')]
#[CoversMethod(CreateTable::class, 'processConstraints')]
#[CoversMethod(CreateTable::class, 'processStatementEnd')]
#[CoversMethod(CreateTable::class, 'setOption')]
#[CoversMethod(CreateTable::class, 'setOptions')]
#[CoversMethod(CreateTable::class, 'getOptions')]
#[CoversMethod(CreateTable::class, 'processTableOptions')]
class CreateTableTest extends TestCase
{
    #[Test]
    public function addColumn(): void
    {
        $column = $this->getMockBuilder(ColumnInterface::class)->getMock();
        $ct     = new CreateTable();

        // First mutation
        $result = $ct->addColumn($column);

        // Verify fluent interface
        static::assertSame($ct, $result);

        // Verify the first mutation occurred
        $state = $ct->getRawState('columns');
        static::assertIsArray($state);
        static::assertCount(1, $state);
        static::assertInstanceOf(ColumnInterface::class, $state[0]);

        // Second mutation to verify mutability (columns accumulate)
        $column2 = $this->getMockBuilder(ColumnInterface::class)->getMock();
        $ct->addColumn($column2);

        // Verify the instance was actually mutated
        $state2 = $ct->getRawState('columns');
        static::assertCount(2, $state2);
        static::assertInstanceOf(ColumnInterface::class, $state2[1]);
    }

    #[Test]
    public function addConstraint(): void
    {
        $constraint = $this->getMockBuilder(ConstraintInterface::class)->getMock();
        $ct         = new CreateTable();

        // First mutation
        $result = $ct->addConstraint($constraint);

        // Verify fluent interface
        static::assertSame($ct, $result);

        // Verify the first mutation occurred
        $state = $ct->getRawState('constraints');
        static::assertIsArray($state);
        static::assertCount(1, $state);
        static::assertInstanceOf(ConstraintInterface::class, $state[0]);

        // Second mutation to verify mutability (constraints accumulate)
        $constraint2 = $this->getMockBuilder(ConstraintInterface::class)->getMock();
        $ct->addConstraint($constraint2);

        // Verify the instance was actually mutated
        $state2 = $ct->getRawState('constraints');
        static::assertCount(2, $state2);
        static::assertInstanceOf(ConstraintInterface::class, $state2[1]);
    }

    #[Test]
    public function chainedOperations(): void
    {
        $ct   = new CreateTable();
        $col1 = $this->getMockBuilder(ColumnInterface::class)->getMock();
        $col2 = $this->getMockBuilder(ColumnInterface::class)->getMock();
        $con  = $this->getMockBuilder(ConstraintInterface::class)->getMock();

        $result = $ct->setTable('products')
            ->setTemporary(true)
            ->addColumn($col1)
            ->addColumn($col2)
            ->addConstraint($con);

        static::assertSame($ct, $result);
        static::assertSame('products', $ct->getRawState(CreateTable::TABLE));
        static::assertTrue($ct->isTemporary());
        static::assertCount(2, $ct->getRawState(CreateTable::COLUMNS));
        static::assertCount(1, $ct->getRawState(CreateTable::CONSTRAINTS));
    }

    #[Test]
    public function constructorWithTableIdentifier(): void
    {
        $tableId = new TableIdentifier('bar', 'foo');
        $ct      = new CreateTable($tableId);

        $rawState = $ct->getRawState();
        static::assertSame($tableId, $rawState[CreateTable::TABLE]);
    }

    #[Test]
    public function constructorWithTemporaryFlag(): void
    {
        $ct = new CreateTable('test', true);
        static::assertTrue($ct->isTemporary());
        static::assertSame('test', $ct->getRawState(CreateTable::TABLE));

        $ct2 = new CreateTable('test', false);
        static::assertFalse($ct2->isTemporary());
    }

    #[Test]
    public function emptyTableConstruction(): void
    {
        $ct = new CreateTable();
        static::assertSame('', $ct->getRawState(CreateTable::TABLE));
        static::assertFalse($ct->isTemporary());
        static::assertEmpty($ct->getRawState(CreateTable::COLUMNS));
        static::assertEmpty($ct->getRawState(CreateTable::CONSTRAINTS));
    }

    #[Test]
    public function getOptionsReturnsEmpty(): void
    {
        $ct = new CreateTable('foo');
        static::assertEquals([], $ct->getOptions());
    }

    #[Test]
    public function getRawStateIncludesTableOptions(): void
    {
        $ct = new CreateTable('foo');
        $ct->setOption('engine', new Literal('InnoDB'));

        $rawState = $ct->getRawState();

        static::assertArrayHasKey(CreateTable::TABLE_OPTIONS, $rawState);
        static::assertEquals(['engine' => new Literal('InnoDB')], $rawState[CreateTable::TABLE_OPTIONS]);
    }

    #[Test]
    public function getRawStateReturnsAllState(): void
    {
        $ct  = new CreateTable('users');
        $col = $this->getMockBuilder(ColumnInterface::class)->getMock();
        $con = $this->getMockBuilder(ConstraintInterface::class)->getMock();

        $ct->addColumn($col);
        $ct->addConstraint($con);

        $rawState = $ct->getRawState();

        static::assertIsArray($rawState);
        static::assertArrayHasKey(CreateTable::TABLE, $rawState);
        static::assertArrayHasKey(CreateTable::COLUMNS, $rawState);
        static::assertArrayHasKey(CreateTable::CONSTRAINTS, $rawState);

        static::assertSame('users', $rawState[CreateTable::TABLE]);
        static::assertEquals([$col], $rawState[CreateTable::COLUMNS]);
        static::assertEquals([$con], $rawState[CreateTable::CONSTRAINTS]);
    }

    #[Test]
    public function getRawStateWithInvalidKey(): void
    {
        $ct = new CreateTable('test');
        $ct->addColumn($this->getMockBuilder(ColumnInterface::class)->getMock());

        // Non-existent key should return full array
        $rawState = $ct->getRawState('invalid_key');
        static::assertIsArray($rawState);
        static::assertArrayHasKey(CreateTable::TABLE, $rawState);
    }

    #[Test]
    public function getSqlString(): void
    {
        $ct = new CreateTable('foo');
        static::assertSame("CREATE TABLE \"foo\" ( \n)", $ct->getSqlString());

        $ct = new CreateTable('foo', true);
        static::assertSame("CREATE TEMPORARY TABLE \"foo\" ( \n)", $ct->getSqlString());

        $ct = new CreateTable('foo');
        $ct->addColumn(new Column('bar'));
        static::assertSame("CREATE TABLE \"foo\" ( \n    \"bar\" INTEGER NOT NULL \n)", $ct->getSqlString());

        $ct = new CreateTable('foo', true);
        $ct->addColumn(new Column('bar'));
        static::assertSame("CREATE TEMPORARY TABLE \"foo\" ( \n    \"bar\" INTEGER NOT NULL \n)", $ct->getSqlString());

        $ct = new CreateTable('foo', true);
        $ct->addColumn(new Column('bar'));
        $ct->addColumn(new Column('baz'));
        static::assertSame(
            "CREATE TEMPORARY TABLE \"foo\" ( \n    \"bar\" INTEGER NOT NULL,\n    \"baz\" INTEGER NOT NULL \n)",
            $ct->getSqlString(),
        );

        $ct = new CreateTable('foo');
        $ct->addColumn(new Column('bar'));
        $ct->addConstraint(new Constraint\PrimaryKey('bat'));
        static::assertSame(
            "CREATE TABLE \"foo\" ( \n    \"bar\" INTEGER NOT NULL , \n    PRIMARY KEY (\"bat\") \n)",
            $ct->getSqlString(),
        );

        $ct = new CreateTable('foo');
        $ct->addConstraint(new Constraint\PrimaryKey('bar'));
        $ct->addConstraint(new Constraint\PrimaryKey('bat'));
        static::assertSame(
            "CREATE TABLE \"foo\" ( \n    PRIMARY KEY (\"bar\"),\n    PRIMARY KEY (\"bat\") \n)",
            $ct->getSqlString(),
        );

        $ct = new CreateTable(new TableIdentifier('foo'));
        $ct->addColumn(new Column('bar'));
        static::assertSame("CREATE TABLE \"foo\" ( \n    \"bar\" INTEGER NOT NULL \n)", $ct->getSqlString());

        $ct = new CreateTable(new TableIdentifier('bar', 'foo'));
        $ct->addColumn(new Column('baz'));
        static::assertSame("CREATE TABLE \"foo\".\"bar\" ( \n    \"baz\" INTEGER NOT NULL \n)", $ct->getSqlString());
    }

    #[Test]
    public function getSqlStringWithBoolOption(): void
    {
        $ct = new CreateTable('foo');
        $ct->addColumn(new Column('bar'));
        $ct->setOption('pack_keys', true);

        static::assertSame(
            "CREATE TABLE \"foo\" ( \n    \"bar\" INTEGER NOT NULL \n) PACK_KEYS = 1",
            $ct->getSqlString(),
        );
    }

    #[Test]
    public function getSqlStringWithIntOption(): void
    {
        $ct = new CreateTable('foo');
        $ct->addColumn(new Column('bar'));
        $ct->setOption('auto_increment', 100);

        static::assertSame(
            "CREATE TABLE \"foo\" ( \n    \"bar\" INTEGER NOT NULL \n) AUTO_INCREMENT = 100",
            $ct->getSqlString(),
        );
    }

    #[Test]
    public function getSqlStringWithLiteralOption(): void
    {
        $ct = new CreateTable('foo');
        $ct->addColumn(new Column('bar'));
        $ct->setOption('engine', new Literal('InnoDB'));

        static::assertSame(
            "CREATE TABLE \"foo\" ( \n    \"bar\" INTEGER NOT NULL \n) ENGINE = InnoDB",
            $ct->getSqlString(),
        );
    }

    #[Test]
    public function getSqlStringWithMultipleOptions(): void
    {
        $ct = new CreateTable('foo');
        $ct->addColumn(new Column('bar'));
        $ct->setOption('engine', new Literal('InnoDB'));
        $ct->setOption('auto_increment', 100);

        static::assertSame(
            "CREATE TABLE \"foo\" ( \n    \"bar\" INTEGER NOT NULL \n) ENGINE = InnoDB AUTO_INCREMENT = 100",
            $ct->getSqlString(),
        );
    }

    #[Test]
    public function getSqlStringWithNoOptionsUnchanged(): void
    {
        $ct = new CreateTable('foo');
        $ct->addColumn(new Column('bar'));

        static::assertSame(
            "CREATE TABLE \"foo\" ( \n    \"bar\" INTEGER NOT NULL \n)",
            $ct->getSqlString(),
        );
    }

    #[Test]
    public function getSqlStringWithStringOption(): void
    {
        $ct = new CreateTable('foo');
        $ct->addColumn(new Column('bar'));
        $ct->setOption('comment', 'My table');

        static::assertSame(
            "CREATE TABLE \"foo\" ( \n    \"bar\" INTEGER NOT NULL \n) COMMENT = 'My table'",
            $ct->getSqlString(),
        );
    }

    #[Test]
    public function ifNotExists(): void
    {
        $ct = new CreateTable('foo');
        static::assertFalse($ct->getIfNotExists());

        $result = $ct->ifNotExists();
        static::assertSame($ct, $result);
        static::assertTrue($ct->getIfNotExists());

        static::assertSame("CREATE TABLE IF NOT EXISTS \"foo\" ( \n)", $ct->getSqlString());
    }

    #[Test]
    public function ifNotExistsCombinedWithTemporary(): void
    {
        $ct = new CreateTable('foo', true);
        $ct->ifNotExists();

        static::assertSame("CREATE TEMPORARY TABLE IF NOT EXISTS \"foo\" ( \n)", $ct->getSqlString());
    }

    #[Test]
    public function ifNotExistsDisable(): void
    {
        $ct = new CreateTable('foo');
        $ct->ifNotExists();
        static::assertTrue($ct->getIfNotExists());

        $ct->ifNotExists(false);
        static::assertFalse($ct->getIfNotExists());

        static::assertSame("CREATE TABLE \"foo\" ( \n)", $ct->getSqlString());
    }

    #[Test]
    public function isTemporary(): void
    {
        $ct = new CreateTable();
        static::assertFalse($ct->isTemporary());
        $ct->setTemporary(true);
        static::assertTrue($ct->isTemporary());
    }

    #[Test]
    public function multipleColumns(): void
    {
        $ct = new CreateTable('users');
        $ct->addColumn(new Column('id'));
        $ct->addColumn(new Column('name'));
        $ct->addColumn(new Column('email'));

        $columns = $ct->getRawState(CreateTable::COLUMNS);
        static::assertCount(3, $columns);

        $sql = $ct->getSqlString();
        static::assertStringContainsString('"id"', $sql);
        static::assertStringContainsString('"name"', $sql);
        static::assertStringContainsString('"email"', $sql);
    }

    #[Test]
    public function multipleConstraints(): void
    {
        $ct = new CreateTable('orders');
        $ct->addConstraint(new Constraint\PrimaryKey('id'));
        $ct->addConstraint(new Constraint\UniqueKey('order_number'));

        $constraints = $ct->getRawState(CreateTable::CONSTRAINTS);
        static::assertCount(2, $constraints);

        $sql = $ct->getSqlString();
        static::assertStringContainsString('PRIMARY KEY', $sql);
        static::assertStringContainsString('UNIQUE', $sql);
    }

    /**
     * test object construction
     */
    #[Test]
    public function objectConstruction(): void
    {
        $ct = new CreateTable('foo', true);
        static::assertSame('foo', $ct->getRawState(CreateTable::TABLE));
        static::assertTrue($ct->isTemporary());
    }

    #[Test]
    public function setOptionFluentInterface(): void
    {
        $ct     = new CreateTable('foo');
        $result = $ct->setOption('engine', new Literal('InnoDB'));

        static::assertSame($ct, $result);
        static::assertEquals(['engine' => new Literal('InnoDB')], $ct->getOptions());
    }

    #[Test]
    public function setOptionsReplacesAll(): void
    {
        $ct = new CreateTable('foo');
        $ct->setOption('engine', new Literal('InnoDB'));

        $ct->setOptions(['charset' => new Literal('utf8mb4')]);

        static::assertEquals(['charset' => new Literal('utf8mb4')], $ct->getOptions());
    }

    #[Test]
    public function setTable(): void
    {
        $ct = new CreateTable();

        // Verify initial state
        static::assertSame('', $ct->getRawState('table'));

        // First mutation
        $result = $ct->setTable('test');

        // Verify fluent interface
        static::assertSame($ct, $result);

        // Verify the first mutation occurred
        static::assertSame('test', $ct->getRawState('table'));

        // Second mutation to verify mutability
        $ct->setTable('another_table');

        // Verify the instance was actually mutated
        static::assertSame('another_table', $ct->getRawState('table'));
    }

    #[Test]
    public function setTableAfterConstruction(): void
    {
        $ct = new CreateTable();
        static::assertSame('', $ct->getRawState(CreateTable::TABLE));

        $ct->setTable('new_table');
        static::assertSame('new_table', $ct->getRawState(CreateTable::TABLE));

        // Test that setTable is chainable
        $result = $ct->setTable('another_table');
        static::assertSame($ct, $result);
        static::assertSame('another_table', $ct->getRawState(CreateTable::TABLE));
    }

    #[Test]
    public function setTemporary(): void
    {
        $ct = new CreateTable();
        static::assertSame($ct, $ct->setTemporary(false));
        static::assertFalse($ct->isTemporary());
        $ct->setTemporary(true);
        static::assertTrue($ct->isTemporary());
        $ct->setTemporary('yes');
        static::assertTrue($ct->isTemporary());

        static::assertStringStartsWith('CREATE TEMPORARY TABLE', $ct->getSqlString());
    }
}
