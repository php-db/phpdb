<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\StatementContainer;
use PhpDb\Sql\AbstractSql;
use PhpDb\Sql\Insert;
use PhpDbTest\AdapterTestTrait;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[CoversMethod(Insert::class, 'processInsert')]
#[CoversMethod(AbstractSql::class, 'resolveParameterNames')]
final class InsertColumnNameTest extends TestCase
{
    use AdapterTestTrait;

    /**
     * @return array<string, array{array<string, int>, string, array<string, int>}>
     */
    public static function insertColumnPreparedProvider(): array
    {
        return [
            'plain'                              => [
                ['title' => 1],
                'INSERT INTO "t" ("title") VALUES (:title)',
                ['title' => 1],
            ],
            'dotted'                             => [
                ['film.title' => 1],
                'INSERT INTO "t" ("film.title") VALUES (:film_title)',
                ['film_title' => 1],
            ],
            'hyphen'                             => [
                ['x-y' => 1],
                'INSERT INTO "t" ("x-y") VALUES (:x_y)',
                ['x_y' => 1],
            ],
            'space'                              => [
                ['a b' => 1],
                'INSERT INTO "t" ("a b") VALUES (:a_b)',
                ['a_b' => 1],
            ],
            'quote character'                    => [
                ['c"d' => 1],
                'INSERT INTO "t" ("c\'d") VALUES (:c_d)',
                ['c_d' => 1],
            ],
            'sanitised names collide'            => [
                ['a.b' => 1, 'a-b' => 2],
                'INSERT INTO "t" ("a.b", "a-b") VALUES (:a_b, :a_b_1)',
                ['a_b' => 1, 'a_b_1' => 2],
            ],
            'sanitised name precedes plain name' => [
                ['a.b' => 1, 'a_b' => 2],
                'INSERT INTO "t" ("a.b", "a_b") VALUES (:a_b_1, :a_b)',
                ['a_b_1' => 1, 'a_b' => 2],
            ],
        ];
    }

    /**
     * @return array<string, array{array<string, int>, string}>
     */
    public static function insertColumnSqlProvider(): array
    {
        return [
            'plain'           => [['title' => 1], 'INSERT INTO "t" ("title") VALUES (\'1\')'],
            'dotted'          => [['film.title' => 1], 'INSERT INTO "t" ("film.title") VALUES (\'1\')'],
            'hyphen'          => [['x-y' => 1], 'INSERT INTO "t" ("x-y") VALUES (\'1\')'],
            'space'           => [['a b' => 1], 'INSERT INTO "t" ("a b") VALUES (\'1\')'],
            'quote character' => [['c"d' => 1], 'INSERT INTO "t" ("c\'d") VALUES (\'1\')'],
        ];
    }

    #[Test]
    public function keepsIncrementalParameterNamesForPdoDriver(): void
    {
        $driver = $this->createStub(PdoDriverInterface::class);
        $driver->method('getPrepareType')->willReturn('named');
        $driver->method('formatParameterName')->willReturnCallback(static fn(string $name): string => ":{$name}");
        $statement = new StatementContainer();

        (new Insert('t'))->values(['film.title' => 1, 'x-y' => 2, 'plain' => 3])
            ->prepareStatement($this->createMockAdapter($driver), $statement);

        self::assertSame(
            [
                'INSERT INTO "t" ("film.title", "x-y", "plain") VALUES (:c_0, :c_1, :c_2)',
                ['c_0' => 1, 'c_1' => 2, 'c_2' => 3],
            ],
            [$statement->getSql(), $statement->getParameterContainer()?->getNamedArray()],
        );
    }

    /**
     * @param array<string, int> $values
     * @param array<string, int> $expectedParameters
     */
    #[Test]
    #[DataProvider('insertColumnPreparedProvider')]
    public function preparesInsertValueWithValidPlaceholder(
        array $values,
        string $expectedSql,
        array $expectedParameters,
    ): void {
        $driver = $this->createStub(DriverInterface::class);
        $driver->method('getPrepareType')->willReturn('named');
        $driver->method('formatParameterName')->willReturnCallback(static fn(string $name): string => ":{$name}");
        $statement = new StatementContainer();

        (new Insert('t'))->values($values)
            ->prepareStatement($this->createMockAdapter($driver), $statement);

        self::assertSame(
            [$expectedSql, $expectedParameters],
            [$statement->getSql(), $statement->getParameterContainer()?->getNamedArray()],
        );
    }

    /**
     * @param array<string, int> $values
     */
    #[Test]
    #[DataProvider('insertColumnSqlProvider')]
    public function rendersInsertColumnAsSingleQuotedIdentifier(array $values, string $expected): void
    {
        $insert = (new Insert('t'))->values($values);

        self::assertSame($expected, $insert->getSqlString(new TrustingSql92Platform()));
    }
}
