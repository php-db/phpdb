<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Platform;

use Closure;
use PhpDb\Sql\Ddl\AlterTable;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\DropTable;
use PhpDb\Sql\Delete;
use PhpDb\Sql\Insert;
use PhpDb\Sql\Platform\AbstractPlatform;
use PhpDb\Sql\Platform\Platform;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;
use PhpDb\Sql\Update;
use PhpDbTest\TestAsset\DialectSelect;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[CoversMethod(AbstractPlatform::class, 'alterTable')]
#[CoversMethod(AbstractPlatform::class, 'createTable')]
#[CoversMethod(AbstractPlatform::class, 'delete')]
#[CoversMethod(AbstractPlatform::class, 'dropTable')]
#[CoversMethod(AbstractPlatform::class, 'insert')]
#[CoversMethod(AbstractPlatform::class, 'select')]
#[CoversMethod(AbstractPlatform::class, 'update')]
final class AbstractPlatformTest extends TestCase
{
    /** @return array<string, array{Closure(AbstractPlatform): object, class-string}> */
    public static function statementProvider(): array
    {
        return [
            'select'      => [static fn(AbstractPlatform $p): object => $p->select('foo'), Select::class],
            'insert'      => [static fn(AbstractPlatform $p): object => $p->insert('foo'), Insert::class],
            'update'      => [static fn(AbstractPlatform $p): object => $p->update('foo'), Update::class],
            'delete'      => [static fn(AbstractPlatform $p): object => $p->delete('foo'), Delete::class],
            'createTable' => [static fn(AbstractPlatform $p): object => $p->createTable('foo'), CreateTable::class],
            'alterTable'  => [static fn(AbstractPlatform $p): object => $p->alterTable('foo'), AlterTable::class],
            'dropTable'   => [static fn(AbstractPlatform $p): object => $p->dropTable('foo'), DropTable::class],
        ];
    }

    #[Test]
    public function aDialectOverrideReturnsItsOwnSubclassThroughTheStandardReturnType(): void
    {
        $platform = new class extends AbstractPlatform {
            public function quoteValue(string $value): string
            {
                return $this->quoteTrustedValue($value);
            }

            /** @param TableIdentifier|string|array<string, string|TableIdentifier>|null $table */
            public function select(array|string|TableIdentifier|null $table = null): Select
            {
                return new DialectSelect($table);
            }
        };

        $statement = $platform->select('foo');

        static::assertInstanceOf(DialectSelect::class, $statement);
        static::assertSame('foo', $statement->getRawState('table'));
    }

    /**
     * @param Closure(AbstractPlatform): object $build
     * @param class-string $expected
     */
    #[Test]
    #[DataProvider('statementProvider')]
    public function standardPlatformBuildsTheStandardStatement(Closure $build, string $expected): void
    {
        static::assertInstanceOf($expected, $build(new Platform()));
    }

    #[Test]
    public function standardPlatformPassesTheTableToTheStatement(): void
    {
        $select = (new Platform())->select('foo');

        static::assertSame('foo', $select->getRawState('table'));
    }
}
