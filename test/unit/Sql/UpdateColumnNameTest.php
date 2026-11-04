<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\StatementContainer;
use PhpDb\Sql\AbstractSql;
use PhpDb\Sql\Update;
use PhpDbTest\AdapterTestTrait;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[CoversMethod(Update::class, 'processSet')]
#[CoversMethod(Update::class, 'quoteSetColumn')]
#[CoversMethod(AbstractSql::class, 'resolveParameterNames')]
final class UpdateColumnNameTest extends TestCase
{
    use AdapterTestTrait;

    /**
     * @return array<string, array{array<string, int>, string, array<string, int>}>
     */
    public static function setColumnPreparedProvider(): array
    {
        return [
            'plain'                              => [
                ['title' => 1],
                'UPDATE "t" SET "title" = :title',
                ['title' => 1],
            ],
            'dotted'                             => [
                ['film.title' => 1],
                'UPDATE "t" SET "film"."title" = :film_title',
                ['film_title' => 1],
            ],
            'hyphen'                             => [
                ['x-y' => 1],
                'UPDATE "t" SET "x-y" = :x_y',
                ['x_y' => 1],
            ],
            'space'                              => [
                ['a b' => 1],
                'UPDATE "t" SET "a b" = :a_b',
                ['a_b' => 1],
            ],
            'quote character'                    => [
                ['c"d' => 1],
                'UPDATE "t" SET "c\'d" = :c_d',
                ['c_d' => 1],
            ],
            'sanitised names collide'            => [
                ['a.b' => 1, 'a-b' => 2],
                'UPDATE "t" SET "a"."b" = :a_b, "a-b" = :a_b_1',
                ['a_b' => 1, 'a_b_1' => 2],
            ],
            'sanitised name precedes plain name' => [
                ['a.b' => 1, 'a_b' => 2],
                'UPDATE "t" SET "a"."b" = :a_b_1, "a_b" = :a_b',
                ['a_b_1' => 1, 'a_b' => 2],
            ],
            'suffixed name is also plain'        => [
                ['a.b' => 1, 'a_b_1' => 2, 'a_b' => 3],
                'UPDATE "t" SET "a"."b" = :a_b_2, "a_b_1" = :a_b_1, "a_b" = :a_b',
                ['a_b_2' => 1, 'a_b_1' => 2, 'a_b' => 3],
            ],
        ];
    }

    /**
     * @return array<string, array{array<string, int>, string}>
     */
    public static function setColumnSqlProvider(): array
    {
        return [
            'plain'           => [['title' => 1], 'UPDATE "t" SET "title" = \'1\''],
            'dotted'          => [['film.title' => 1], 'UPDATE "t" SET "film"."title" = \'1\''],
            'hyphen'          => [['x-y' => 1], 'UPDATE "t" SET "x-y" = \'1\''],
            'space'           => [['a b' => 1], 'UPDATE "t" SET "a b" = \'1\''],
            'quote character' => [['c"d' => 1], 'UPDATE "t" SET "c\'d" = \'1\''],
            'dotted hyphen'   => [['f-1.x-y' => 1], 'UPDATE "t" SET "f-1"."x-y" = \'1\''],
        ];
    }

    #[Test]
    public function keepsIncrementalParameterNamesForPdoDriver(): void
    {
        $driver = $this->createStub(PdoDriverInterface::class);
        $driver->method('getPrepareType')->willReturn('named');
        $driver->method('formatParameterName')->willReturnCallback(static fn(string $name): string => ":{$name}");
        $statement = new StatementContainer();

        (new Update('t'))->set(['film.title' => 1, 'x-y' => 2, 'plain' => 3])
            ->prepareStatement($this->createMockAdapter($driver), $statement);

        self::assertSame(
            [
                'UPDATE "t" SET "film"."title" = :c_0, "x-y" = :c_1, "plain" = :c_2',
                ['c_0' => 1, 'c_1' => 2, 'c_2' => 3],
            ],
            [$statement->getSql(), $statement->getParameterContainer()?->getNamedArray()],
        );
    }

    /**
     * @param array<string, int> $set
     * @param array<string, int> $expectedParameters
     */
    #[Test]
    #[DataProvider('setColumnPreparedProvider')]
    public function preparesSetColumnWithQuotedIdentifierChainAndValidPlaceholder(
        array $set,
        string $expectedSql,
        array $expectedParameters,
    ): void {
        $driver = $this->createStub(DriverInterface::class);
        $driver->method('getPrepareType')->willReturn('named');
        $driver->method('formatParameterName')->willReturnCallback(static fn(string $name): string => ":{$name}");
        $statement = new StatementContainer();

        (new Update('t'))->set($set)->prepareStatement($this->createMockAdapter($driver), $statement);

        self::assertSame(
            [$expectedSql, $expectedParameters],
            [$statement->getSql(), $statement->getParameterContainer()?->getNamedArray()],
        );
    }

    /**
     * @param array<string, int> $set
     */
    #[Test]
    #[DataProvider('setColumnSqlProvider')]
    public function rendersSetColumnAsQuotedIdentifierChain(array $set, string $expected): void
    {
        $update = (new Update('t'))->set($set);

        self::assertSame($expected, $update->getSqlString(new TrustingSql92Platform()));
    }
}
