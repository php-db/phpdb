<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use Closure;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\StatementContainer;
use PhpDb\Sql\AbstractPreparableSql;
use PhpDb\Sql\AbstractSql;
use PhpDb\Sql\Combine;
use PhpDb\Sql\Delete;
use PhpDb\Sql\Expression;
use PhpDb\Sql\Insert;
use PhpDb\Sql\Predicate\In;
use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\Update;
use PhpDbTest\TestAsset\SelectDecorator;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(AbstractSql::class, 'getSqlString')]
#[CoversMethod(AbstractSql::class, 'processExpression')]
#[CoversMethod(AbstractSql::class, 'processSubSelect')]
#[CoversMethod(AbstractSql::class, 'resetRenderState')]
#[CoversMethod(AbstractPreparableSql::class, 'prepareStatement')]
#[CoversMethod(Combine::class, 'buildSqlString')]
final class RenderStateTest extends TestCase
{
    /** @return array<string, array{Closure(): PreparableSqlInterface, string, array<string, mixed>}> */
    public static function statementProvider(): array
    {
        return [
            'select'                                      => [
                static fn(): PreparableSqlInterface => (new Select('t'))->columns([
                    'id',
                    'n' => new Expression('COALESCE(x, ?)', [0]),
                ])
                    ->where(['a' => 1, 'b' => 2])
                    ->where([new In('c', (new Select('u'))->columns(['id'])->where(['d' => 3]))])
                    ->order(new Expression('FIELD(id, ?)', [4]))
                    ->limit(10)
                    ->offset(20),
                'SELECT "t"."id" AS "id", COALESCE(x, :n1) AS "n" FROM "t" WHERE "a" = :where1 AND "b" = :where2'
                    . ' AND "c" IN (SELECT "u"."id" AS "id" FROM "u" WHERE "d" = :subselect1where1)'
                    . ' ORDER BY FIELD(id, :expr0Param1) LIMIT :limit OFFSET :offset',
                [
                    'n1'               => 0,
                    'where1'           => 1,
                    'where2'           => 2,
                    'subselect1where1' => 3,
                    'expr0Param1'      => 4,
                    'limit'            => 10,
                    'offset'           => 20,
                ],
            ],
            'update'                                      => [
                static fn(): PreparableSqlInterface => (new Update('t'))->set([
                    'a' => 1,
                    'b' => new Expression('b + ?', [2]),
                ])
                    ->where(['c' => 3])
                    ->where([new In('d', (new Select('u'))->columns(['id'])->where(['e' => 4]))]),
                'UPDATE "t" SET "a" = :a, "b" = b + :expr0Param1 WHERE "c" = :where1'
                    . ' AND "d" IN (SELECT "u"."id" AS "id" FROM "u" WHERE "e" = :subselect1where1)',
                ['a' => 1, 'expr0Param1' => 2, 'where1' => 3, 'subselect1where1' => 4],
            ],
            'insert'                                      => [
                static fn(): PreparableSqlInterface => (new Insert('t'))
                    ->values(['a' => 1, 'b' => new Expression('LOWER(?)', ['X'])]),
                'INSERT INTO "t" ("a", "b") VALUES (:a, LOWER(:expr0Param1))',
                ['a' => 1, 'expr0Param1' => 'X'],
            ],
            'insert from select'                          => [
                static fn(): PreparableSqlInterface => (new Insert('t'))->columns(['a'])
                    ->select((new Select('u'))->columns(['a'])->where(['b' => 1])),
                'INSERT INTO "t" ("a") SELECT "u"."a" AS "a" FROM "u" WHERE "b" = :subselect1where1',
                ['subselect1where1' => 1],
            ],
            'delete'                                      => [
                static fn(): PreparableSqlInterface => (new Delete('t'))->where(['a' => 1])
                    ->where([new In('b', (new Select('u'))->columns(['id'])->where(['c' => 2]))]),
                'DELETE FROM "t" WHERE "a" = :where1'
                    . ' AND "b" IN (SELECT "u"."id" AS "id" FROM "u" WHERE "c" = :subselect1where1)',
                ['where1' => 1, 'subselect1where1' => 2],
            ],
            'combine'                                     => [
                static fn(): PreparableSqlInterface => new Combine([
                    (new Select('t'))->where(['a' => 1]),
                    (new Select('u'))->where(['a' => 2]),
                ]),
                '(SELECT "t".* FROM "t" WHERE "a" = :subselect1where1)'
                    . ' UNION (SELECT "u".* FROM "u" WHERE "a" = :subselect2where1)',
                ['subselect1where1' => 1, 'subselect2where1' => 2],
            ],
            'unnamed expressions in parent and subselect' => [
                static fn(): PreparableSqlInterface => (new Select('t'))->where([
                    new In('a', (new Select('u'))->columns(['id'])->order(new Expression('FIELD(id, ?)', [1]))),
                ])
                    ->order(new Expression('FIELD(id, ?)', [2])),
                'SELECT "t".* FROM "t" WHERE "a" IN'
                    . ' (SELECT "u"."id" AS "id" FROM "u" ORDER BY FIELD(id, :expr0Param1))'
                    . ' ORDER BY FIELD(id, :expr1Param1)',
                ['expr0Param1' => 1, 'expr1Param1' => 2],
            ],
        ];
    }

    #[Test]
    #[Group('unit')]
    public function platformDecoratorRendersTheSameParameterNamesAsItsSubjectOnEveryPrepare(): void
    {
        $subject = (new Select('t'))->where(['a' => 1])
            ->where([new In('b', (new Select('u'))->columns(['id'])->where(['c' => 2]))]);
        $decorator = new SelectDecorator();
        $expected  = [
            'SELECT "t".* FROM "t" WHERE "a" = :where1'
                . ' AND "b" IN (SELECT "u"."id" AS "id" FROM "u" WHERE "c" = :subselect1where1)',
            ['where1' => 1, 'subselect1where1' => 2],
        ];

        static::assertSame(
            [$expected, $expected, $expected],
            [
                $this->prepare($subject),
                $this->prepare($decorator->setSubject($subject)),
                $this->prepare($decorator->setSubject($subject)),
            ],
        );
    }

    /**
     * @param Closure(): PreparableSqlInterface $factory
     * @param array<string, mixed> $expectedParameters
     */
    #[Test]
    #[Group('unit')]
    #[DataProvider('statementProvider')]
    public function preparingTheSameStatementTwiceRendersIdenticalSqlAndParameterNames(
        Closure $factory,
        string $expectedSql,
        array $expectedParameters,
    ): void {
        $statement = $factory();

        static::assertSame(
            [
                [$expectedSql, $expectedParameters],
                [$expectedSql, $expectedParameters],
            ],
            [$this->prepare($statement), $this->prepare($statement)],
        );
    }

    #[Test]
    #[Group('unit')]
    public function renderingAsStringBetweenPreparesDoesNotChangeParameterNames(): void
    {
        $select = (new Select('t'))->where(['a' => 1])
            ->where([new In('b', (new Select('u'))->columns(['id'])->where(['c' => 2]))]);

        $first = $this->prepare($select);
        $select->getSqlString(new TrustingSql92Platform());

        static::assertSame($first, $this->prepare($select));
    }

    #[Test]
    #[Group('unit')]
    public function selectPreviouslyRenderedAsSubselectHasNoSubselectPrefixAtTopLevel(): void
    {
        $inner = (new Select('u'))->columns(['id'])->where(['c' => 2]);
        $this->prepare((new Select('t'))->where([new In('b', $inner)]));

        static::assertSame(
            ['SELECT "u"."id" AS "id" FROM "u" WHERE "c" = :where1', ['where1' => 2]],
            $this->prepare($inner),
        );
    }

    /**
     * @return array{?string, array<string, mixed>}
     */
    private function prepare(PreparableSqlInterface $statement): array
    {
        $driver = $this->createStub(DriverInterface::class);
        $driver->method('formatParameterName')->willReturnCallback(static fn(string $name): string => ":{$name}");

        $adapter = $this->createStub(AdapterInterface::class);
        $adapter->method('getDriver')->willReturn($driver);
        $adapter->method('getPlatform')->willReturn(new TrustingSql92Platform());

        $container = $statement->prepareStatement($adapter, new StatementContainer());

        return [$container->getSql(), $container->getParameterContainer()?->getNamedArray() ?? []];
    }
}
