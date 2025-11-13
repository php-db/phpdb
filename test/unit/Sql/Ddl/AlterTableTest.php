<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl;

use PhpDb\Sql\Ddl\AlterTable;
use PhpDb\Sql\Ddl\Column;
use PhpDb\Sql\Ddl\Column\ColumnInterface;
use PhpDb\Sql\Ddl\Constraint;
use PhpDb\Sql\Ddl\Constraint\ConstraintInterface;
use PhpDb\Sql\TableIdentifier;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

use function str_replace;

#[CoversMethod(AlterTable::class, 'setTable')]
#[CoversMethod(AlterTable::class, 'addColumn')]
#[CoversMethod(AlterTable::class, 'changeColumn')]
#[CoversMethod(AlterTable::class, 'dropColumn')]
#[CoversMethod(AlterTable::class, 'dropConstraint')]
#[CoversMethod(AlterTable::class, 'addConstraint')]
#[CoversMethod(AlterTable::class, 'dropIndex')]
#[CoversMethod(AlterTable::class, 'getSqlString')]
class AlterTableTest extends TestCase
{
    public function testSetTable(): void
    {
        $at = new AlterTable();
        self::assertEquals('', $at->getRawState('table'));
        self::assertSame($at, $at->setTable('test'));
        self::assertEquals('test', $at->getRawState('table'));
    }

    public function testAddColumn(): void
    {
        $at = new AlterTable();
        /** @var ColumnInterface $colMock */
        $colMock = $this->getMockBuilder(ColumnInterface::class)->getMock();
        self::assertSame($at, $at->addColumn($colMock));
        self::assertEquals([$colMock], $at->getRawState($at::ADD_COLUMNS));
    }

    public function testChangeColumn(): void
    {
        $at = new AlterTable();
        /** @var ColumnInterface $colMock */
        $colMock = $this->getMockBuilder(ColumnInterface::class)->getMock();
        self::assertSame($at, $at->changeColumn('newname', $colMock));
        self::assertEquals(['newname' => $colMock], $at->getRawState($at::CHANGE_COLUMNS));
    }

    public function testDropColumn(): void
    {
        $at = new AlterTable();
        self::assertSame($at, $at->dropColumn('foo'));
        self::assertEquals(['foo'], $at->getRawState($at::DROP_COLUMNS));
    }

    public function testDropConstraint(): void
    {
        $at = new AlterTable();
        self::assertSame($at, $at->dropConstraint('foo'));
        self::assertEquals(['foo'], $at->getRawState($at::DROP_CONSTRAINTS));
    }

    public function testAddConstraint(): void
    {
        $at = new AlterTable();
        /** @var ConstraintInterface $conMock */
        $conMock = $this->getMockBuilder(ConstraintInterface::class)->getMock();
        self::assertSame($at, $at->addConstraint($conMock));
        self::assertEquals([$conMock], $at->getRawState($at::ADD_CONSTRAINTS));
    }

    public function testDropIndex(): void
    {
        $at = new AlterTable();
        self::assertSame($at, $at->dropIndex('foo'));
        self::assertEquals(['foo'], $at->getRawState($at::DROP_INDEXES));
    }

    /**
     * @todo Implement testGetSqlString().
     */
    public function testGetSqlString(): void
    {
        $at = new AlterTable('foo');
        $at->addColumn(new Column\Varchar('another', 255));
        $at->changeColumn('name', new Column\Varchar('new_name', 50));
        $at->dropColumn('foo');
        $at->addConstraint(new Constraint\ForeignKey('my_fk', 'other_id', 'other_table', 'id', 'CASCADE', 'CASCADE'));
        $at->dropConstraint('my_constraint');
        $at->dropIndex('my_index');
        $expected = <<<EOS
ALTER TABLE "foo"
 ADD COLUMN "another" VARCHAR(255) NOT NULL,
 CHANGE COLUMN "name" "new_name" VARCHAR(50) NOT NULL,
 DROP COLUMN "foo",
 ADD CONSTRAINT "my_fk" FOREIGN KEY ("other_id") REFERENCES "other_table" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
 DROP CONSTRAINT "my_constraint",
 DROP INDEX "my_index"
EOS;

        $actual = $at->getSqlString();
        self::assertEquals(
            str_replace(["\r", "\n"], '', $expected),
            str_replace(["\r", "\n"], '', $actual)
        );

        $at = new AlterTable(new TableIdentifier('foo'));
        $at->addColumn(new Column\Column('bar'));
        $this->assertEquals("ALTER TABLE \"foo\"\n ADD COLUMN \"bar\" INTEGER NOT NULL", $at->getSqlString());

        $at = new AlterTable(new TableIdentifier('bar', 'foo'));
        $at->addColumn(new Column\Column('baz'));
        $this->assertEquals("ALTER TABLE \"foo\".\"bar\"\n ADD COLUMN \"baz\" INTEGER NOT NULL", $at->getSqlString());
    }

    public function testConstructorWithTable(): void
    {
        $at = new AlterTable('test_table');
        self::assertEquals('test_table', $at->getRawState('table'));
    }

    public function testConstructorWithTableIdentifier(): void
    {
        $tableId = new TableIdentifier('bar', 'foo');
        $at = new AlterTable($tableId);

        // Get full raw state to avoid type issue with getRawState('table')
        $rawState = $at->getRawState();
        self::assertSame($tableId, $rawState['table']);
    }

    public function testConstructorWithEmptyTable(): void
    {
        $at = new AlterTable();
        self::assertEquals('', $at->getRawState('table'));
    }

    public function testGetRawStateReturnsAllState(): void
    {
        $at = new AlterTable('test');
        $colMock = $this->getMockBuilder(ColumnInterface::class)->getMock();
        $conMock = $this->getMockBuilder(ConstraintInterface::class)->getMock();

        $at->addColumn($colMock);
        $at->changeColumn('old_col', $colMock);
        $at->dropColumn('drop_col');
        $at->addConstraint($conMock);
        $at->dropConstraint('drop_con');
        $at->dropIndex('drop_idx');

        $rawState = $at->getRawState();

        self::assertIsArray($rawState);
        self::assertArrayHasKey(AlterTable::TABLE, $rawState);
        self::assertArrayHasKey(AlterTable::ADD_COLUMNS, $rawState);
        self::assertArrayHasKey(AlterTable::CHANGE_COLUMNS, $rawState);
        self::assertArrayHasKey(AlterTable::DROP_COLUMNS, $rawState);
        self::assertArrayHasKey(AlterTable::ADD_CONSTRAINTS, $rawState);
        self::assertArrayHasKey(AlterTable::DROP_CONSTRAINTS, $rawState);
        self::assertArrayHasKey(AlterTable::DROP_INDEXES, $rawState);

        self::assertEquals('test', $rawState[AlterTable::TABLE]);
        self::assertEquals([$colMock], $rawState[AlterTable::ADD_COLUMNS]);
        self::assertEquals(['old_col' => $colMock], $rawState[AlterTable::CHANGE_COLUMNS]);
        self::assertEquals(['drop_col'], $rawState[AlterTable::DROP_COLUMNS]);
        self::assertEquals([$conMock], $rawState[AlterTable::ADD_CONSTRAINTS]);
        self::assertEquals(['drop_con'], $rawState[AlterTable::DROP_CONSTRAINTS]);
        self::assertEquals(['drop_idx'], $rawState[AlterTable::DROP_INDEXES]);
    }

    public function testGetRawStateWithSpecificKey(): void
    {
        $at = new AlterTable('my_table');
        $at->dropColumn('col1');
        $at->dropColumn('col2');

        self::assertEquals('my_table', $at->getRawState(AlterTable::TABLE));
        self::assertEquals(['col1', 'col2'], $at->getRawState(AlterTable::DROP_COLUMNS));
        self::assertEquals([], $at->getRawState(AlterTable::ADD_COLUMNS));
    }

    public function testMultipleColumnsAndConstraints(): void
    {
        $at = new AlterTable('users');

        $col1 = new Column\Varchar('email', 255);
        $col2 = new Column\Integer('age');
        $col3 = new Column\Text('bio');

        $at->addColumn($col1);
        $at->addColumn($col2);
        $at->addColumn($col3);

        self::assertCount(3, $at->getRawState(AlterTable::ADD_COLUMNS));

        $sql = $at->getSqlString();
        self::assertStringContainsString('ADD COLUMN "email"', $sql);
        self::assertStringContainsString('ADD COLUMN "age"', $sql);
        self::assertStringContainsString('ADD COLUMN "bio"', $sql);
    }

    public function testMultipleDropOperations(): void
    {
        $at = new AlterTable('products');

        $at->dropColumn('old_col1');
        $at->dropColumn('old_col2');
        $at->dropConstraint('old_fk');
        $at->dropIndex('old_idx');

        $sql = $at->getSqlString();
        self::assertStringContainsString('DROP COLUMN "old_col1"', $sql);
        self::assertStringContainsString('DROP COLUMN "old_col2"', $sql);
        self::assertStringContainsString('DROP CONSTRAINT "old_fk"', $sql);
        self::assertStringContainsString('DROP INDEX "old_idx"', $sql);
    }

    public function testChainedOperations(): void
    {
        $at = new AlterTable();
        $col = $this->getMockBuilder(ColumnInterface::class)->getMock();
        $con = $this->getMockBuilder(ConstraintInterface::class)->getMock();

        $result = $at->setTable('test')
            ->addColumn($col)
            ->dropColumn('old')
            ->addConstraint($con)
            ->dropConstraint('old_fk')
            ->dropIndex('old_idx');

        self::assertSame($at, $result);
        self::assertEquals('test', $at->getRawState(AlterTable::TABLE));
    }
}
