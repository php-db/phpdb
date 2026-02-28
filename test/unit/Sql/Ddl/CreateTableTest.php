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
    /**
     * test object construction
     */
    public function testObjectConstruction(): void
    {
        $ct = new CreateTable('foo', true);
        self::assertEquals('foo', $ct->getRawState(CreateTable::TABLE));
        self::assertTrue($ct->isTemporary());
    }

    public function testSetTemporary(): void
    {
        $ct = new CreateTable();
        self::assertSame($ct, $ct->setTemporary(false));
        self::assertFalse($ct->isTemporary());
        $ct->setTemporary(true);
        self::assertTrue($ct->isTemporary());
        $ct->setTemporary('yes');
        self::assertTrue($ct->isTemporary());

        self::assertStringStartsWith('CREATE TEMPORARY TABLE', $ct->getSqlString());
    }

    public function testIsTemporary(): void
    {
        $ct = new CreateTable();
        self::assertFalse($ct->isTemporary());
        $ct->setTemporary(true);
        self::assertTrue($ct->isTemporary());
    }

    public function testSetTable(): void
    {
        $ct = new CreateTable();

        // Verify initial state
        self::assertEquals('', $ct->getRawState('table'));

        // First mutation
        $result = $ct->setTable('test');

        // Verify fluent interface
        self::assertSame($ct, $result);

        // Verify the first mutation occurred
        self::assertEquals('test', $ct->getRawState('table'));

        // Second mutation to verify mutability
        $ct->setTable('another_table');

        // Verify the instance was actually mutated
        self::assertEquals('another_table', $ct->getRawState('table'));
    }

    public function testAddColumn(): void
    {
        $column = $this->getMockBuilder(ColumnInterface::class)->getMock();
        $ct     = new CreateTable();

        // First mutation
        $result = $ct->addColumn($column);

        // Verify fluent interface
        self::assertSame($ct, $result);

        // Verify the first mutation occurred
        $state = $ct->getRawState('columns');
        self::assertIsArray($state);
        self::assertCount(1, $state);
        self::assertInstanceOf(ColumnInterface::class, $state[0]);

        // Second mutation to verify mutability (columns accumulate)
        $column2 = $this->getMockBuilder(ColumnInterface::class)->getMock();
        $ct->addColumn($column2);

        // Verify the instance was actually mutated
        $state2 = $ct->getRawState('columns');
        self::assertCount(2, $state2);
        self::assertInstanceOf(ColumnInterface::class, $state2[1]);
    }

    public function testAddConstraint(): void
    {
        $constraint = $this->getMockBuilder(ConstraintInterface::class)->getMock();
        $ct         = new CreateTable();

        // First mutation
        $result = $ct->addConstraint($constraint);

        // Verify fluent interface
        self::assertSame($ct, $result);

        // Verify the first mutation occurred
        $state = $ct->getRawState('constraints');
        self::assertIsArray($state);
        self::assertCount(1, $state);
        self::assertInstanceOf(ConstraintInterface::class, $state[0]);

        // Second mutation to verify mutability (constraints accumulate)
        $constraint2 = $this->getMockBuilder(ConstraintInterface::class)->getMock();
        $ct->addConstraint($constraint2);

        // Verify the instance was actually mutated
        $state2 = $ct->getRawState('constraints');
        self::assertCount(2, $state2);
        self::assertInstanceOf(ConstraintInterface::class, $state2[1]);
    }

    public function testGetSqlString(): void
    {
        $ct = new CreateTable('foo');
        self::assertEquals("CREATE TABLE \"foo\" ( \n)", $ct->getSqlString());

        $ct = new CreateTable('foo', true);
        self::assertEquals("CREATE TEMPORARY TABLE \"foo\" ( \n)", $ct->getSqlString());

        $ct = new CreateTable('foo');
        $ct->addColumn(new Column('bar'));
        self::assertEquals("CREATE TABLE \"foo\" ( \n    \"bar\" INTEGER NOT NULL \n)", $ct->getSqlString());

        $ct = new CreateTable('foo', true);
        $ct->addColumn(new Column('bar'));
        self::assertEquals("CREATE TEMPORARY TABLE \"foo\" ( \n    \"bar\" INTEGER NOT NULL \n)", $ct->getSqlString());

        $ct = new CreateTable('foo', true);
        $ct->addColumn(new Column('bar'));
        $ct->addColumn(new Column('baz'));
        self::assertEquals(
            "CREATE TEMPORARY TABLE \"foo\" ( \n    \"bar\" INTEGER NOT NULL,\n    \"baz\" INTEGER NOT NULL \n)",
            $ct->getSqlString()
        );

        $ct = new CreateTable('foo');
        $ct->addColumn(new Column('bar'));
        $ct->addConstraint(new Constraint\PrimaryKey('bat'));
        self::assertEquals(
            "CREATE TABLE \"foo\" ( \n    \"bar\" INTEGER NOT NULL , \n    PRIMARY KEY (\"bat\") \n)",
            $ct->getSqlString()
        );

        $ct = new CreateTable('foo');
        $ct->addConstraint(new Constraint\PrimaryKey('bar'));
        $ct->addConstraint(new Constraint\PrimaryKey('bat'));
        self::assertEquals(
            "CREATE TABLE \"foo\" ( \n    PRIMARY KEY (\"bar\"),\n    PRIMARY KEY (\"bat\") \n)",
            $ct->getSqlString()
        );

        $ct = new CreateTable(new TableIdentifier('foo'));
        $ct->addColumn(new Column('bar'));
        self::assertEquals("CREATE TABLE \"foo\" ( \n    \"bar\" INTEGER NOT NULL \n)", $ct->getSqlString());

        $ct = new CreateTable(new TableIdentifier('bar', 'foo'));
        $ct->addColumn(new Column('baz'));
        self::assertEquals("CREATE TABLE \"foo\".\"bar\" ( \n    \"baz\" INTEGER NOT NULL \n)", $ct->getSqlString());
    }

    public function testConstructorWithTableIdentifier(): void
    {
        $tableId = new TableIdentifier('bar', 'foo');
        $ct      = new CreateTable($tableId);

        $rawState = $ct->getRawState();
        self::assertSame($tableId, $rawState[CreateTable::TABLE]);
    }

    public function testConstructorWithTemporaryFlag(): void
    {
        $ct = new CreateTable('test', true);
        self::assertTrue($ct->isTemporary());
        self::assertEquals('test', $ct->getRawState(CreateTable::TABLE));

        $ct2 = new CreateTable('test', false);
        self::assertFalse($ct2->isTemporary());
    }

    public function testGetRawStateReturnsAllState(): void
    {
        $ct  = new CreateTable('users');
        $col = $this->getMockBuilder(ColumnInterface::class)->getMock();
        $con = $this->getMockBuilder(ConstraintInterface::class)->getMock();

        $ct->addColumn($col);
        $ct->addConstraint($con);

        $rawState = $ct->getRawState();

        self::assertIsArray($rawState);
        self::assertArrayHasKey(CreateTable::TABLE, $rawState);
        self::assertArrayHasKey(CreateTable::COLUMNS, $rawState);
        self::assertArrayHasKey(CreateTable::CONSTRAINTS, $rawState);

        self::assertEquals('users', $rawState[CreateTable::TABLE]);
        self::assertEquals([$col], $rawState[CreateTable::COLUMNS]);
        self::assertEquals([$con], $rawState[CreateTable::CONSTRAINTS]);
    }

    public function testGetRawStateWithInvalidKey(): void
    {
        $ct = new CreateTable('test');
        $ct->addColumn($this->getMockBuilder(ColumnInterface::class)->getMock());

        // Non-existent key should return full array
        $rawState = $ct->getRawState('invalid_key');
        self::assertIsArray($rawState);
        self::assertArrayHasKey(CreateTable::TABLE, $rawState);
    }

    public function testChainedOperations(): void
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

        self::assertSame($ct, $result);
        self::assertEquals('products', $ct->getRawState(CreateTable::TABLE));
        self::assertTrue($ct->isTemporary());
        self::assertCount(2, $ct->getRawState(CreateTable::COLUMNS));
        self::assertCount(1, $ct->getRawState(CreateTable::CONSTRAINTS));
    }

    public function testMultipleColumns(): void
    {
        $ct = new CreateTable('users');
        $ct->addColumn(new Column('id'));
        $ct->addColumn(new Column('name'));
        $ct->addColumn(new Column('email'));

        $columns = $ct->getRawState(CreateTable::COLUMNS);
        self::assertCount(3, $columns);

        $sql = $ct->getSqlString();
        self::assertStringContainsString('"id"', $sql);
        self::assertStringContainsString('"name"', $sql);
        self::assertStringContainsString('"email"', $sql);
    }

    public function testMultipleConstraints(): void
    {
        $ct = new CreateTable('orders');
        $ct->addConstraint(new Constraint\PrimaryKey('id'));
        $ct->addConstraint(new Constraint\UniqueKey('order_number'));

        $constraints = $ct->getRawState(CreateTable::CONSTRAINTS);
        self::assertCount(2, $constraints);

        $sql = $ct->getSqlString();
        self::assertStringContainsString('PRIMARY KEY', $sql);
        self::assertStringContainsString('UNIQUE', $sql);
    }

    public function testEmptyTableConstruction(): void
    {
        $ct = new CreateTable();
        self::assertEquals('', $ct->getRawState(CreateTable::TABLE));
        self::assertFalse($ct->isTemporary());
        self::assertEmpty($ct->getRawState(CreateTable::COLUMNS));
        self::assertEmpty($ct->getRawState(CreateTable::CONSTRAINTS));
    }

    public function testSetTableAfterConstruction(): void
    {
        $ct = new CreateTable();
        self::assertEquals('', $ct->getRawState(CreateTable::TABLE));

        $ct->setTable('new_table');
        self::assertEquals('new_table', $ct->getRawState(CreateTable::TABLE));

        // Test that setTable is chainable
        $result = $ct->setTable('another_table');
        self::assertSame($ct, $result);
        self::assertEquals('another_table', $ct->getRawState(CreateTable::TABLE));
    }

    public function testSetOptionFluentInterface(): void
    {
        $ct     = new CreateTable('foo');
        $result = $ct->setOption('engine', new Literal('InnoDB'));

        self::assertSame($ct, $result);
        self::assertEquals(['engine' => new Literal('InnoDB')], $ct->getOptions());
    }

    public function testSetOptionsReplacesAll(): void
    {
        $ct = new CreateTable('foo');
        $ct->setOption('engine', new Literal('InnoDB'));

        $ct->setOptions(['charset' => new Literal('utf8mb4')]);

        self::assertEquals(['charset' => new Literal('utf8mb4')], $ct->getOptions());
    }

    public function testGetOptionsReturnsEmpty(): void
    {
        $ct = new CreateTable('foo');
        self::assertEquals([], $ct->getOptions());
    }

    public function testGetRawStateIncludesTableOptions(): void
    {
        $ct = new CreateTable('foo');
        $ct->setOption('engine', new Literal('InnoDB'));

        $rawState = $ct->getRawState();

        self::assertArrayHasKey(CreateTable::TABLE_OPTIONS, $rawState);
        self::assertEquals(['engine' => new Literal('InnoDB')], $rawState[CreateTable::TABLE_OPTIONS]);
    }

    public function testGetSqlStringWithLiteralOption(): void
    {
        $ct = new CreateTable('foo');
        $ct->addColumn(new Column('bar'));
        $ct->setOption('engine', new Literal('InnoDB'));

        self::assertEquals(
            "CREATE TABLE \"foo\" ( \n    \"bar\" INTEGER NOT NULL \n) ENGINE = InnoDB",
            $ct->getSqlString()
        );
    }

    public function testGetSqlStringWithStringOption(): void
    {
        $ct = new CreateTable('foo');
        $ct->addColumn(new Column('bar'));
        $ct->setOption('comment', 'My table');

        self::assertEquals(
            "CREATE TABLE \"foo\" ( \n    \"bar\" INTEGER NOT NULL \n) COMMENT = 'My table'",
            $ct->getSqlString()
        );
    }

    public function testGetSqlStringWithIntOption(): void
    {
        $ct = new CreateTable('foo');
        $ct->addColumn(new Column('bar'));
        $ct->setOption('auto_increment', 100);

        self::assertEquals(
            "CREATE TABLE \"foo\" ( \n    \"bar\" INTEGER NOT NULL \n) AUTO_INCREMENT = 100",
            $ct->getSqlString()
        );
    }

    public function testGetSqlStringWithBoolOption(): void
    {
        $ct = new CreateTable('foo');
        $ct->addColumn(new Column('bar'));
        $ct->setOption('pack_keys', true);

        self::assertEquals(
            "CREATE TABLE \"foo\" ( \n    \"bar\" INTEGER NOT NULL \n) PACK_KEYS = 1",
            $ct->getSqlString()
        );
    }

    public function testGetSqlStringWithMultipleOptions(): void
    {
        $ct = new CreateTable('foo');
        $ct->addColumn(new Column('bar'));
        $ct->setOption('engine', new Literal('InnoDB'));
        $ct->setOption('auto_increment', 100);

        self::assertEquals(
            "CREATE TABLE \"foo\" ( \n    \"bar\" INTEGER NOT NULL \n) ENGINE = InnoDB AUTO_INCREMENT = 100",
            $ct->getSqlString()
        );
    }

    public function testGetSqlStringWithNoOptionsUnchanged(): void
    {
        $ct = new CreateTable('foo');
        $ct->addColumn(new Column('bar'));

        self::assertEquals(
            "CREATE TABLE \"foo\" ( \n    \"bar\" INTEGER NOT NULL \n)",
            $ct->getSqlString()
        );
    }

    public function testIfNotExists(): void
    {
        $ct = new CreateTable('foo');
        self::assertFalse($ct->getIfNotExists());

        $result = $ct->ifNotExists();
        self::assertSame($ct, $result);
        self::assertTrue($ct->getIfNotExists());

        self::assertEquals("CREATE TABLE IF NOT EXISTS \"foo\" ( \n)", $ct->getSqlString());
    }

    public function testIfNotExistsDisable(): void
    {
        $ct = new CreateTable('foo');
        $ct->ifNotExists();
        self::assertTrue($ct->getIfNotExists());

        $ct->ifNotExists(false);
        self::assertFalse($ct->getIfNotExists());

        self::assertEquals("CREATE TABLE \"foo\" ( \n)", $ct->getSqlString());
    }

    public function testIfNotExistsCombinedWithTemporary(): void
    {
        $ct = new CreateTable('foo', true);
        $ct->ifNotExists();

        self::assertEquals("CREATE TEMPORARY TABLE IF NOT EXISTS \"foo\" ( \n)", $ct->getSqlString());
    }
}
