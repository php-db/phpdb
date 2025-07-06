<?php

namespace PhpDbTest\Sql\Platform\Mysql\Ddl;

use PhpDb\Adapter\Platform\Mysql;
use PhpDb\Sql\Ddl\AlterTable;
use PhpDb\Sql\Ddl\Column\Column;
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
use PhpDb\Sql\Platform\Mysql\Ddl\AlterTableDecorator;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(AlterTableDecorator::class, 'setSubject')]
#[CoversMethod(AlterTableDecorator::class, 'getSqlString')]
final class AlterTableDecoratorTest extends TestCase
{
    public function testSetSubject(): void
    {
        $ctd = new AlterTableDecorator();
        $ct  = new AlterTable();
        self::assertSame($ctd, $ctd->setSubject($ct));
    }

    public function testGetSqlString(): void
    {
        $ctd = new AlterTableDecorator();
        $ct  = new AlterTable('foo');
        $ctd->setSubject($ct);

        $col = new Column('bar');
        $col->setOption('zerofill', true);
        $col->setOption('unsigned', true);
        $col->setOption('identity', true);
        $col->setOption('comment', 'baz');
        $col->setOption('after', 'bar');
        $col->addConstraint(new PrimaryKey());
        $ct->addColumn($col);

        self::assertEquals(
            "ALTER TABLE `foo`\n ADD COLUMN `bar` INTEGER UNSIGNED ZEROFILL "
            . "NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'baz' AFTER `bar`",
            @$ctd->getSqlString(new Mysql())
        );
    }
}
