<?php

namespace LaminasTest\Db\Sql\Platform\Mysql\Ddl;

use Laminas\Db\Adapter\Platform\Mysql;
use Laminas\Db\Sql\Ddl\Column\Column;
use Laminas\Db\Sql\Ddl\Constraint\PrimaryKey;
use Laminas\Db\Sql\Ddl\CreateTable;
use Laminas\Db\Sql\Platform\Mysql\Ddl\CreateTableDecorator;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(CreateTableDecorator::class, 'setSubject')]
#[CoversMethod(CreateTableDecorator::class, 'getSqlString')]
final class CreateTableDecoratorTest extends TestCase
{
    public function testSetSubject(): void
    {
        $ctd = new CreateTableDecorator();
        $ct  = new CreateTable();
        self::assertSame($ctd, $ctd->setSubject($ct));
    }

    public function testGetSqlString(): void
    {
        $ctd = new CreateTableDecorator();
        $ct  = new CreateTable('foo');
        $ctd->setSubject($ct);

        $col = new Column('bar');
        $col->setOption('zerofill', true);
        $col->setOption('unsigned', true);
        $col->setOption('identity', true);
        $col->setOption('column-format', 'FIXED');
        $col->setOption('storage', 'memory');
        $col->setOption('comment', 'baz');
        $col->addConstraint(new PrimaryKey());
        $ct->addColumn($col);

        self::assertEquals(
            // @codingStandardsIgnoreStart
            "CREATE TABLE `foo` ( \n    `bar` INTEGER UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'baz' COLUMN_FORMAT FIXED STORAGE MEMORY \n)",
            // @codingStandardsIgnoreEnd
            @$ctd->getSqlString(new Mysql())
        );
    }
}
