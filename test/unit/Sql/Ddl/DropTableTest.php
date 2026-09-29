<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl;

use PhpDb\Sql\Ddl\DropTable;
use PhpDb\Sql\TableIdentifier;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(DropTable::class, '__construct')]
#[CoversMethod(DropTable::class, 'ifExists')]
#[CoversMethod(DropTable::class, 'getIfExists')]
#[CoversMethod(DropTable::class, 'getSqlString')]
#[CoversMethod(DropTable::class, 'processTable')]
class DropTableTest extends TestCase
{
    #[Test]
    public function getSqlString(): void
    {
        $dt = new DropTable('foo');
        static::assertSame('DROP TABLE "foo"', $dt->getSqlString());

        $dt = new DropTable(new TableIdentifier('foo'));
        static::assertSame('DROP TABLE "foo"', $dt->getSqlString());

        $dt = new DropTable(new TableIdentifier('bar', 'foo'));
        static::assertSame('DROP TABLE "foo"."bar"', $dt->getSqlString());
    }

    #[Test]
    public function ifExists(): void
    {
        $dt = new DropTable('foo');
        static::assertFalse($dt->getIfExists());

        $result = $dt->ifExists();
        static::assertSame($dt, $result);
        static::assertTrue($dt->getIfExists());

        static::assertSame('DROP TABLE IF EXISTS "foo"', $dt->getSqlString());
    }

    #[Test]
    public function ifExistsDisable(): void
    {
        $dt = new DropTable('foo');
        $dt->ifExists();
        static::assertTrue($dt->getIfExists());

        $dt->ifExists(false);
        static::assertFalse($dt->getIfExists());

        static::assertSame('DROP TABLE "foo"', $dt->getSqlString());
    }

    #[Test]
    public function ifExistsWithTableIdentifier(): void
    {
        $dt = new DropTable(new TableIdentifier('bar', 'foo'));
        $dt->ifExists();

        static::assertSame('DROP TABLE IF EXISTS "foo"."bar"', $dt->getSqlString());
    }
}
