<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl;

use PhpDb\Sql\Ddl\Column\Column;
use PhpDb\Sql\Ddl\Column\ColumnInterface;
use PhpDb\Sql\Ddl\Constraint;
use PhpDb\Sql\Ddl\Constraint\ConstraintInterface;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\TableIdentifier;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

use function array_pop;

#[CoversMethod(CreateTable::class, '__construct')]
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

    public function testSetTable(): CreateTable
    {
        $ct = new CreateTable();
        self::assertEquals('', $ct->getRawState('table'));
        $ct->setTable('test');
        return $ct;
    }

    #[Depends('testSetTable')]
    public function testRawStateViaTable(CreateTable $ct): void
    {
        self::assertEquals('test', $ct->getRawState('table'));
    }

    public function testAddColumn(): CreateTable
    {
        $column = $this->getMockBuilder(ColumnInterface::class)->getMock();
        $ct     = new CreateTable();
        self::assertSame($ct, $ct->addColumn($column));
        return $ct;
    }

    #[Depends('testAddColumn')]
    public function testRawStateViaColumn(CreateTable $ct): void
    {
        $state = $ct->getRawState('columns');
        self::assertIsArray($state);
        $column = array_pop($state);
        self::assertInstanceOf(ColumnInterface::class, $column);
    }

    public function testAddConstraint(): CreateTable
    {
        $constraint = $this->getMockBuilder(ConstraintInterface::class)->getMock();
        $ct         = new CreateTable();
        self::assertSame($ct, $ct->addConstraint($constraint));
        return $ct;
    }

    #[Depends('testAddConstraint')]
    public function testRawStateViaConstraint(CreateTable $ct): void
    {
        $state = $ct->getRawState('constraints');
        self::assertIsArray($state);
        $constraint = array_pop($state);
        self::assertInstanceOf(ConstraintInterface::class, $constraint);
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
}
