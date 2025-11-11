<?php

namespace PhpDbTest\Sql\Platform\SqlServer\Ddl;

use PhpDb\Sql\Ddl\Column\Column;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Platform\SqlServer\Ddl\CreateTableDecorator;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(CreateTableDecorator::class, 'getSqlString')]
class CreateTableDecoratorTest extends TestCase
{
    public function testGetSqlString(): void
    {
        $ctd = new CreateTableDecorator();

        $ct = new CreateTable('foo');
        self::assertEquals("CREATE TABLE \"foo\" ( \n)", $ctd->setSubject($ct)->getSqlString());

        $ct = new CreateTable('foo', true);
        self::assertEquals("CREATE TABLE \"#foo\" ( \n)", $ctd->setSubject($ct)->getSqlString());

        $ct = new CreateTable('foo');
        $ct->addColumn(new Column('bar'));
        self::assertEquals(
            "CREATE TABLE \"foo\" ( \n    \"bar\" INTEGER NOT NULL \n)",
            $ctd->setSubject($ct)->getSqlString()
        );

        $ct = new CreateTable('foo', true);
        $ct->addColumn(new Column('bar'));
        self::assertEquals(
            "CREATE TABLE \"#foo\" ( \n    \"bar\" INTEGER NOT NULL \n)",
            $ctd->setSubject($ct)->getSqlString()
        );
    }
}
