<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use Closure;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\StatementContainer;
use PhpDb\Sql\Expression;
use PhpDb\Sql\Predicate\Operator;
use PhpDb\Sql\Select;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function ksort;

#[Group('unit')]
#[CoversMethod(Select::class, 'processSelect')]
#[CoversMethod(Select::class, 'appendParameters')]
#[CoversMethod(Select::class, 'copyParameters')]
final class SelectParameterOrderTest extends TestCase
{
    /**
     * @return array<string, array{Closure(): Select, string, list<mixed>}>
     */
    public static function outOfOrderSelectProvider(): array
    {
        return [
            'quantifier before column'                 => [
                static fn(): Select => (new Select('t'))->columns(['c' => new Expression('?', ['COLUMN'])])
                    ->quantifier(new Expression('TOP ?', ['QUANTIFIER'])),
                'SELECT TOP ? ? AS "c" FROM "t"',
                ['QUANTIFIER', 'COLUMN'],
            ],
            'column before from sub-select'            => [
                static fn(): Select => (new Select(['t' => (new Select('src'))->where(['x' => 'FROM'])]))
                    ->columns(['c' => new Expression('?', ['COLUMN'])]),
                'SELECT ? AS "c" FROM (SELECT "src".* FROM "src" WHERE "x" = ?) AS "t"',
                ['COLUMN', 'FROM'],
            ],
            'quantifier before from sub-select'        => [
                static fn(): Select => (new Select(['t' => (new Select('src'))->where(['x' => 'FROM'])]))
                    ->quantifier(new Expression('TOP ?', ['QUANTIFIER'])),
                'SELECT TOP ? "t".* FROM (SELECT "src".* FROM "src" WHERE "x" = ?) AS "t"',
                ['QUANTIFIER', 'FROM'],
            ],
            'join column before from sub-select'       => [
                static fn(): Select => (new Select(['t' => (new Select('src'))->where(['x' => 'FROM'])]))
                    ->join(['j' => 'jt'], 'j.id = t.id', ['jc' => new Expression('?', ['JOIN-COLUMN'])]),
                'SELECT "t".*, ? AS "jc" FROM (SELECT "src".* FROM "src" WHERE "x" = ?) AS "t"'
                    . ' INNER JOIN "jt" AS "j" ON "j"."id" = "t"."id"',
                ['JOIN-COLUMN', 'FROM'],
            ],
            'column sub-select before from sub-select' => [
                static fn(): Select => (new Select(['t' => (new Select('src'))->where(['x' => 'FROM'])]))
                    ->columns(['c' => (new Select('cs'))->where(['y' => 'COLUMN-SUB-SELECT'])]),
                'SELECT (SELECT "cs".* FROM "cs" WHERE "y" = ?) AS "c"'
                    . ' FROM (SELECT "src".* FROM "src" WHERE "x" = ?) AS "t"',
                ['COLUMN-SUB-SELECT', 'FROM'],
            ],
            'quantifier, column and from sub-select'   => [
                static fn(): Select => (new Select(['t' => (new Select('src'))->where([
                    'x' => 'FROM',
                ])]))->columns(['c' => new Expression('?', ['COLUMN'])])
                    ->quantifier(new Expression('TOP ?', ['QUANTIFIER'])),
                'SELECT TOP ? ? AS "c" FROM (SELECT "src".* FROM "src" WHERE "x" = ?) AS "t"',
                ['QUANTIFIER', 'COLUMN', 'FROM'],
            ],
            'nested from sub-select'                   => [
                static fn(): Select => new Select([
                    'outer' => (new Select(['inner' => (new Select('src'))->where(['x' => 'INNER-FROM'])]))
                        ->columns(['c' => new Expression('?', ['OUTER-COLUMN'])]),
                ]),
                'SELECT "outer".* FROM (SELECT ? AS "c"'
                    . ' FROM (SELECT "src".* FROM "src" WHERE "x" = ?) AS "inner") AS "outer"',
                ['OUTER-COLUMN', 'INNER-FROM'],
            ],
            'every clause'                             => [
                static fn(): Select => (new Select(['t' => (new Select('src'))->where([
                    'x' => 'FROM',
                ])]))->columns(['c' => new Expression('?', ['COLUMN'])])
                    ->quantifier(new Expression('TOP ?', ['QUANTIFIER']))
                    ->join(
                        ['j' => (new Select('js'))->where(['z' => 'JOIN-TABLE'])],
                        new Operator('j.k', Operator::OPERATOR_EQUAL_TO, 'JOIN-ON'),
                        ['jc' => new Expression('?', ['JOIN-COLUMN'])],
                    )
                    ->where(['w' => 'WHERE'])
                    ->group(new Expression('?', ['GROUP']))
                    ->having(['h' => 'HAVING'])
                    ->order(new Expression('?', ['ORDER']))
                    ->limit(5)
                    ->offset(10),
                'SELECT TOP ? ? AS "c", ? AS "jc" FROM (SELECT "src".* FROM "src" WHERE "x" = ?) AS "t"'
                    . ' INNER JOIN (SELECT "js".* FROM "js" WHERE "z" = ?) AS "j" ON "j"."k" = ?'
                    . ' WHERE "w" = ? GROUP BY ? HAVING "h" = ? ORDER BY ? LIMIT ? OFFSET ?',
                [
                    'QUANTIFIER',
                    'COLUMN',
                    'JOIN-COLUMN',
                    'FROM',
                    'JOIN-TABLE',
                    'JOIN-ON',
                    'WHERE',
                    'GROUP',
                    'HAVING',
                    'ORDER',
                    5,
                    10,
                ],
            ],
        ];
    }

    #[Test]
    public function appendsAfterParametersAlreadyInTheContainer(): void
    {
        $select = (new Select(['t' => (new Select('src'))->where(['x' => 'FROM'])]))
            ->columns(['c' => new Expression('?', ['COLUMN'])]);

        $parameterContainer = new ParameterContainer(['existing' => 'EXISTING']);

        $this->prepare($select, $this->createDriver('positional'), $parameterContainer);

        static::assertSame(['EXISTING', 'COLUMN', 'FROM'], $parameterContainer->getPositionalArray());
    }

    /**
     * @param Closure(): Select $build
     * @param list<mixed> $expectedValues
     */
    #[DataProvider('outOfOrderSelectProvider')]
    #[Test]
    public function bindsPositionalParametersInPlaceholderOrder(
        Closure $build,
        string $expectedSql,
        array $expectedValues,
    ): void {
        $parameterContainer = new ParameterContainer();

        $sql = $this->prepare($build(), $this->createDriver('positional'), $parameterContainer);

        static::assertSame(
            [$expectedSql, $expectedValues],
            [$sql, $parameterContainer->getPositionalArray()],
        );
    }

    #[Test]
    public function keepsNamedParameterNamesAndSql(): void
    {
        $select = (new Select(['t' => (new Select('src'))->where(['x' => 'FROM'])]))->columns([
            'c'  => new Expression('?', ['COLUMN']),
            'sq' => (new Select('cs'))->where(['y' => 'COLUMN-SUB-SELECT']),
        ])
            ->quantifier(new Expression('TOP ?', ['QUANTIFIER']))
            ->join(['j' => 'jt'], 'j.id = t.id', ['jc' => new Expression('?', ['JOIN-COLUMN'])])
            ->where(['w' => 'WHERE'])
            ->limit(5)
            ->offset(10);

        $parameterContainer = new ParameterContainer();

        $sql = $this->prepare($select, $this->createDriver('named'), $parameterContainer);

        $namedParameters = $parameterContainer->getNamedArray();
        ksort($namedParameters);

        static::assertSame(
            [
                'SELECT TOP :quantifier1 :c1 AS "c", (SELECT "cs".* FROM "cs" WHERE "y" = :subselect2where1) AS "sq",'
                    . ' :jc1 AS "jc" FROM (SELECT "src".* FROM "src" WHERE "x" = :subselect1where1) AS "t"'
                    . ' INNER JOIN "jt" AS "j" ON "j"."id" = "t"."id" WHERE "w" = :where1'
                    . ' LIMIT :limit OFFSET :offset',
                [
                    'c1'               => 'COLUMN',
                    'jc1'              => 'JOIN-COLUMN',
                    'limit'            => 5,
                    'offset'           => 10,
                    'quantifier1'      => 'QUANTIFIER',
                    'subselect1where1' => 'FROM',
                    'subselect2where1' => 'COLUMN-SUB-SELECT',
                    'where1'           => 'WHERE',
                ],
            ],
            [$sql, $namedParameters],
        );
    }

    #[Test]
    public function keepsParameterTypeOfReorderedParameters(): void
    {
        $select = (new Select(['t' => (new Select('src'))->limit(5)]))
            ->columns(['c' => new Expression('?', ['COLUMN'])]);

        $parameterContainer = new ParameterContainer();

        $this->prepare($select, $this->createDriver('positional'), $parameterContainer);

        static::assertSame(
            [['COLUMN', 5], ParameterContainer::TYPE_INTEGER],
            [$parameterContainer->getPositionalArray(), $parameterContainer->offsetGetErrata('subselect1limit')],
        );
    }

    private function createDriver(string $prepareType): DriverInterface
    {
        $formatParameterName = static fn(string $name): string => 'named' === $prepareType ? ":{$name}" : '?';

        $driver = $this->createStub(DriverInterface::class);
        $driver->method('getPrepareType')->willReturn($prepareType);
        $driver->method('formatParameterName')->willReturnCallback($formatParameterName);

        return $driver;
    }

    private function prepare(Select $select, DriverInterface $driver, ParameterContainer $parameterContainer): string
    {
        $adapter = $this->createStub(AdapterInterface::class);
        $adapter->method('getPlatform')->willReturn(new TrustingSql92Platform());
        $adapter->method('getDriver')->willReturn($driver);

        return (string) $select->prepareStatement($adapter, new StatementContainer(null, $parameterContainer))
            ->getSql();
    }
}
