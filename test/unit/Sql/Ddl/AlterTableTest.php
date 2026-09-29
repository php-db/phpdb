<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl;

use PhpDb\Sql\Ddl\AlterTable;
use PhpDb\Sql\Ddl\Column;
use PhpDb\Sql\Ddl\Column\ColumnInterface;
use PhpDb\Sql\Ddl\Constraint;
use PhpDb\Sql\Ddl\Constraint\ConstraintInterface;
use PhpDb\Sql\Literal;
use PhpDb\Sql\TableIdentifier;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

use function str_replace;

#[CoversMethod(AlterTable::class, '__construct')]
#[CoversMethod(AlterTable::class, 'setTable')]
#[CoversMethod(AlterTable::class, 'addColumn')]
#[CoversMethod(AlterTable::class, 'changeColumn')]
#[CoversMethod(AlterTable::class, 'dropColumn')]
#[CoversMethod(AlterTable::class, 'dropConstraint')]
#[CoversMethod(AlterTable::class, 'addConstraint')]
#[CoversMethod(AlterTable::class, 'dropIndex')]
#[CoversMethod(AlterTable::class, 'getRawState')]
#[CoversMethod(AlterTable::class, 'processTable')]
#[CoversMethod(AlterTable::class, 'processAddColumns')]
#[CoversMethod(AlterTable::class, 'processChangeColumns')]
#[CoversMethod(AlterTable::class, 'processDropColumns')]
#[CoversMethod(AlterTable::class, 'processAddConstraints')]
#[CoversMethod(AlterTable::class, 'processDropConstraints')]
#[CoversMethod(AlterTable::class, 'processDropIndexes')]
#[CoversMethod(AlterTable::class, 'setOption')]
#[CoversMethod(AlterTable::class, 'setOptions')]
#[CoversMethod(AlterTable::class, 'getOptions')]
#[CoversMethod(AlterTable::class, 'processTableOptions')]
class AlterTableTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function addColumn(): void
    {
        $at = new AlterTable();
        /** @var ColumnInterface $colMock */
        $colMock = $this->getMockBuilder(ColumnInterface::class)->getMock();
        static::assertSame($at, $at->addColumn($colMock));
        static::assertEquals([$colMock], $at->getRawState(AlterTable::ADD_COLUMNS));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function addConstraint(): void
    {
        $at = new AlterTable();
        /** @var ConstraintInterface $conMock */
        $conMock = $this->getMockBuilder(ConstraintInterface::class)->getMock();
        static::assertSame($at, $at->addConstraint($conMock));
        static::assertEquals([$conMock], $at->getRawState(AlterTable::ADD_CONSTRAINTS));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function addConstraintGeneratesCorrectSql(): void
    {
        $at = new AlterTable('orders');
        $fk = new Constraint\ForeignKey('fk_user', 'user_id', 'users', 'id');
        $at->addConstraint($fk);

        $sql = $at->getSqlString();
        static::assertStringContainsString('ADD CONSTRAINT', $sql);
        static::assertStringContainsString('"fk_user"', $sql);
        static::assertStringContainsString('FOREIGN KEY', $sql);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function chainedOperations(): void
    {
        $at  = new AlterTable();
        $col = $this->getMockBuilder(ColumnInterface::class)->getMock();
        $con = $this->getMockBuilder(ConstraintInterface::class)->getMock();

        $result = $at->setTable('test')
            ->addColumn($col)
            ->dropColumn('old')
            ->addConstraint($con)
            ->dropConstraint('old_fk')
            ->dropIndex('old_idx');

        static::assertSame($at, $result);
        static::assertSame('test', $at->getRawState(AlterTable::TABLE));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function changeColumn(): void
    {
        $at = new AlterTable();
        /** @var ColumnInterface $colMock */
        $colMock = $this->getMockBuilder(ColumnInterface::class)->getMock();
        static::assertSame($at, $at->changeColumn('newname', $colMock));
        static::assertEquals(['newname' => $colMock], $at->getRawState(AlterTable::CHANGE_COLUMNS));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function changeColumnGeneratesCorrectSql(): void
    {
        $at = new AlterTable('users');
        $at->changeColumn('old_name', new Column\Varchar('new_name', 100));

        $sql = $at->getSqlString();
        static::assertStringContainsString('CHANGE COLUMN', $sql);
        static::assertStringContainsString('"old_name"', $sql);
        static::assertStringContainsString('"new_name"', $sql);
        static::assertStringContainsString('VARCHAR(100)', $sql);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorWithEmptyTable(): void
    {
        $at = new AlterTable();
        static::assertSame('', $at->getRawState('table'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorWithTable(): void
    {
        $at = new AlterTable('test_table');
        static::assertSame('test_table', $at->getRawState('table'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorWithTableIdentifier(): void
    {
        $tableId = new TableIdentifier('bar', 'foo');
        $at      = new AlterTable($tableId);

        // Get full raw state to avoid type issue with getRawState('table')
        $rawState = $at->getRawState();
        static::assertSame($tableId, $rawState['table']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dropColumn(): void
    {
        $at = new AlterTable();
        static::assertSame($at, $at->dropColumn('foo'));
        static::assertEquals(['foo'], $at->getRawState(AlterTable::DROP_COLUMNS));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dropConstraint(): void
    {
        $at = new AlterTable();
        static::assertSame($at, $at->dropConstraint('foo'));
        static::assertEquals(['foo'], $at->getRawState(AlterTable::DROP_CONSTRAINTS));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dropIndex(): void
    {
        $at = new AlterTable();
        static::assertSame($at, $at->dropIndex('foo'));
        static::assertEquals(['foo'], $at->getRawState(AlterTable::DROP_INDEXES));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function emptyAlterTableGeneratesMinimalSql(): void
    {
        $at  = new AlterTable('test_table');
        $sql = $at->getSqlString();

        // Should have ALTER TABLE but no operations
        static::assertStringContainsString('ALTER TABLE "test_table"', $sql);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getOptionsReturnsEmpty(): void
    {
        $at = new AlterTable('foo');
        static::assertEquals([], $at->getOptions());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getRawStateIncludesTableOptions(): void
    {
        $at = new AlterTable('foo');
        $at->setOption('engine', new Literal('InnoDB'));

        $rawState = $at->getRawState();

        static::assertArrayHasKey(AlterTable::TABLE_OPTIONS, $rawState);
        static::assertEquals(['engine' => new Literal('InnoDB')], $rawState[AlterTable::TABLE_OPTIONS]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getRawStateReturnsAllState(): void
    {
        $at      = new AlterTable('test');
        $colMock = $this->getMockBuilder(ColumnInterface::class)->getMock();
        $conMock = $this->getMockBuilder(ConstraintInterface::class)->getMock();

        $at->addColumn($colMock);
        $at->changeColumn('old_col', $colMock);
        $at->dropColumn('drop_col');
        $at->addConstraint($conMock);
        $at->dropConstraint('drop_con');
        $at->dropIndex('drop_idx');

        $rawState = $at->getRawState();

        static::assertIsArray($rawState);
        static::assertArrayHasKey(AlterTable::TABLE, $rawState);
        static::assertArrayHasKey(AlterTable::ADD_COLUMNS, $rawState);
        static::assertArrayHasKey(AlterTable::CHANGE_COLUMNS, $rawState);
        static::assertArrayHasKey(AlterTable::DROP_COLUMNS, $rawState);
        static::assertArrayHasKey(AlterTable::ADD_CONSTRAINTS, $rawState);
        static::assertArrayHasKey(AlterTable::DROP_CONSTRAINTS, $rawState);
        static::assertArrayHasKey(AlterTable::DROP_INDEXES, $rawState);

        static::assertSame('test', $rawState[AlterTable::TABLE]);
        static::assertEquals([$colMock], $rawState[AlterTable::ADD_COLUMNS]);
        static::assertEquals(['old_col' => $colMock], $rawState[AlterTable::CHANGE_COLUMNS]);
        static::assertEquals(['drop_col'], $rawState[AlterTable::DROP_COLUMNS]);
        static::assertEquals([$conMock], $rawState[AlterTable::ADD_CONSTRAINTS]);
        static::assertEquals(['drop_con'], $rawState[AlterTable::DROP_CONSTRAINTS]);
        static::assertEquals(['drop_idx'], $rawState[AlterTable::DROP_INDEXES]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getRawStateWithInvalidKey(): void
    {
        $at     = new AlterTable('test');
        $result = $at->getRawState('invalid_key');

        // Should return full array when key doesn't exist
        static::assertIsArray($result);
        static::assertArrayHasKey(AlterTable::TABLE, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getRawStateWithSpecificKey(): void
    {
        $at = new AlterTable('my_table');
        $at->dropColumn('col1');
        $at->dropColumn('col2');

        static::assertSame('my_table', $at->getRawState(AlterTable::TABLE));
        static::assertEquals(['col1', 'col2'], $at->getRawState(AlterTable::DROP_COLUMNS));
        static::assertEquals([], $at->getRawState(AlterTable::ADD_COLUMNS));
    }

    /**
     * @todo Implement testGetSqlString().
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function getSqlString(): void
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
        static::assertEquals(
            str_replace(["\r", "\n"], '', $expected),
            str_replace(["\r", "\n"], '', $actual),
        );

        $at = new AlterTable(new TableIdentifier('foo'));
        $at->addColumn(new Column\Column('bar'));
        static::assertSame("ALTER TABLE \"foo\"\n ADD COLUMN \"bar\" INTEGER NOT NULL", $at->getSqlString());

        $at = new AlterTable(new TableIdentifier('bar', 'foo'));
        $at->addColumn(new Column\Column('baz'));
        static::assertSame("ALTER TABLE \"foo\".\"bar\"\n ADD COLUMN \"baz\" INTEGER NOT NULL", $at->getSqlString());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getSqlStringWithBoolOption(): void
    {
        $at = new AlterTable('foo');
        $at->setOption('pack_keys', true);

        $sql = $at->getSqlString();
        static::assertStringContainsString('PACK_KEYS = 1', $sql);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getSqlStringWithColumnAndEngineOption(): void
    {
        $at = new AlterTable('foo');
        $at->addColumn(new Column\Column('bar'));
        $at->setOption('engine', new Literal('InnoDB'));

        $sql = $at->getSqlString();
        static::assertStringContainsString('ADD COLUMN "bar" INTEGER NOT NULL', $sql);
        static::assertStringContainsString('ENGINE = InnoDB', $sql);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getSqlStringWithEngineOption(): void
    {
        $at = new AlterTable('foo');
        $at->setOption('engine', new Literal('InnoDB'));

        $sql = $at->getSqlString();
        static::assertStringContainsString('ALTER TABLE "foo"', $sql);
        static::assertStringContainsString('ENGINE = InnoDB', $sql);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getSqlStringWithIntOption(): void
    {
        $at = new AlterTable('foo');
        $at->setOption('auto_increment', 100);

        $sql = $at->getSqlString();
        static::assertStringContainsString('AUTO_INCREMENT = 100', $sql);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getSqlStringWithMultipleOptions(): void
    {
        $at = new AlterTable('foo');
        $at->setOption('engine', new Literal('InnoDB'));
        $at->setOption('auto_increment', 100);

        $sql = $at->getSqlString();
        static::assertStringContainsString('ENGINE = InnoDB', $sql);
        static::assertStringContainsString('AUTO_INCREMENT = 100', $sql);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getSqlStringWithStringOption(): void
    {
        $at = new AlterTable('foo');
        $at->setOption('comment', 'My table');

        $sql = $at->getSqlString();
        static::assertStringContainsString('COMMENT = \'My table\'', $sql);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function mixedOperationsInCorrectOrder(): void
    {
        $at = new AlterTable('complex_table');

        // Add operations in mixed order
        $at->dropColumn('old_col');
        $at->addColumn(new Column\Integer('new_col'));
        $at->changeColumn('existing', new Column\Text('existing_text'));
        $at->addConstraint(new Constraint\ForeignKey('fk_test', 'ref_id', 'refs', 'id'));
        $at->dropConstraint('old_constraint');
        $at->dropIndex('old_index');

        $sql = $at->getSqlString();

        // Verify all operations are present
        static::assertStringContainsString('ADD COLUMN "new_col"', $sql);
        static::assertStringContainsString('CHANGE COLUMN "existing"', $sql);
        static::assertStringContainsString('DROP COLUMN "old_col"', $sql);
        static::assertStringContainsString('ADD CONSTRAINT "fk_test"', $sql);
        static::assertStringContainsString('DROP CONSTRAINT "old_constraint"', $sql);
        static::assertStringContainsString('DROP INDEX "old_index"', $sql);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function multipleChangeColumns(): void
    {
        $at = new AlterTable('products');
        $at->changeColumn('price', new Column\Decimal('cost', 10, 2));
        $at->changeColumn('name', new Column\Varchar('title', 200));

        $sql = $at->getSqlString();
        static::assertStringContainsString('CHANGE COLUMN "price" "cost"', $sql);
        static::assertStringContainsString('CHANGE COLUMN "name" "title"', $sql);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function multipleColumnsAndConstraints(): void
    {
        $at = new AlterTable('users');

        $col1 = new Column\Varchar('email', 255);
        $col2 = new Column\Integer('age');
        $col3 = new Column\Text('bio');

        $at->addColumn($col1);
        $at->addColumn($col2);
        $at->addColumn($col3);

        static::assertCount(3, $at->getRawState(AlterTable::ADD_COLUMNS));

        $sql = $at->getSqlString();
        static::assertStringContainsString('ADD COLUMN "email"', $sql);
        static::assertStringContainsString('ADD COLUMN "age"', $sql);
        static::assertStringContainsString('ADD COLUMN "bio"', $sql);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function multipleConstraints(): void
    {
        $at  = new AlterTable('orders');
        $fk1 = new Constraint\ForeignKey('fk_user', 'user_id', 'users', 'id');
        $fk2 = new Constraint\ForeignKey('fk_product', 'product_id', 'products', 'id');

        $at->addConstraint($fk1);
        $at->addConstraint($fk2);

        $sql = $at->getSqlString();
        static::assertStringContainsString('"fk_user"', $sql);
        static::assertStringContainsString('"fk_product"', $sql);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function multipleDropOperations(): void
    {
        $at = new AlterTable('products');

        $at->dropColumn('old_col1');
        $at->dropColumn('old_col2');
        $at->dropConstraint('old_fk');
        $at->dropIndex('old_idx');

        $sql = $at->getSqlString();
        static::assertStringContainsString('DROP COLUMN "old_col1"', $sql);
        static::assertStringContainsString('DROP COLUMN "old_col2"', $sql);
        static::assertStringContainsString('DROP CONSTRAINT "old_fk"', $sql);
        static::assertStringContainsString('DROP INDEX "old_idx"', $sql);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function setOptionFluentInterface(): void
    {
        $at     = new AlterTable('foo');
        $result = $at->setOption('engine', new Literal('InnoDB'));

        static::assertSame($at, $result);
        static::assertEquals(['engine' => new Literal('InnoDB')], $at->getOptions());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function setOptionsReplacesAll(): void
    {
        $at = new AlterTable('foo');
        $at->setOption('engine', new Literal('InnoDB'));

        $at->setOptions(['charset' => new Literal('utf8mb4')]);

        static::assertEquals(['charset' => new Literal('utf8mb4')], $at->getOptions());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function setTable(): void
    {
        $at = new AlterTable();
        static::assertSame('', $at->getRawState('table'));
        static::assertSame($at, $at->setTable('test'));
        static::assertSame('test', $at->getRawState('table'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function tableIdentifierInChangeColumn(): void
    {
        $at = new AlterTable(new TableIdentifier('table', 'schema'));
        $at->changeColumn('col1', new Column\Integer('col1_new'));

        $sql = $at->getSqlString();
        static::assertStringContainsString('"schema"."table"', $sql);
        static::assertStringContainsString('CHANGE COLUMN "col1" "col1_new"', $sql);
    }
}
