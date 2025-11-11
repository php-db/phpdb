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
}
