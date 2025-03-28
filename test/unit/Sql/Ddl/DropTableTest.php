<?php

namespace LaminasTest\Db\Sql\Ddl;

use Laminas\Db\Sql\Ddl\DropTable;
use Laminas\Db\Sql\TableIdentifier;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(DropTable::class, 'getSqlString')]
class DropTableTest extends TestCase
{
    public function testGetSqlString()
    {
        $dt = new DropTable('foo');
        self::assertEquals('DROP TABLE "foo"', $dt->getSqlString());

        $dt = new DropTable(new TableIdentifier('foo'));
        self::assertEquals('DROP TABLE "foo"', $dt->getSqlString());

        $dt = new DropTable(new TableIdentifier('bar', 'foo'));
        self::assertEquals('DROP TABLE "foo"."bar"', $dt->getSqlString());
    }
}
