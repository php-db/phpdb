<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\Expression;
use PhpDb\Sql\Select;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[CoversMethod(Select::class, 'processSelect')]
#[CoversMethod(Select::class, 'processGroup')]
#[CoversMethod(Select::class, 'resolveColumnValue')]
final class SelectColumnNameTest extends TestCase
{
    /**
     * @return array<string, array{string|Expression, string}>
     */
    public static function groupColumnProvider(): array
    {
        return [
            'plain'                     => ['bar', '"bar"'],
            'dotted'                    => ['t.bar', '"t"."bar"'],
            'hyphen'                    => ['x-y', '"x-y"'],
            'dotted hyphen'             => ['t-1.x-y', '"t-1"."x-y"'],
            'space'                     => ['my col', '"my col"'],
            'quote character'           => ['a"b', '"a\'b"'],
            'arithmetic'                => ['a * b', '"a" * "b"'],
            'arithmetic without spaces' => ['a*b', '"a"*"b"'],
            'expression'                => [new Expression('YEAR(created)'), 'YEAR(created)'],
        ];
    }

    /**
     * @return array<string, array{array<int|string, string|Expression>, string}>
     */
    public static function joinColumnProvider(): array
    {
        return [
            'plain'                     => [['bar'], '"zac"."bar" AS "bar"'],
            'plain aliased'             => [['b' => 'bar'], '"zac"."bar" AS "b"'],
            'dotted'                    => [['t.bar'], '"zac"."t"."bar" AS "t.bar"'],
            'star'                      => [['*'], '"zac".*'],
            'qualified star'            => [['t.*'], '"zac"."t".* AS "t.*"'],
            'hyphen'                    => [['x-y'], '"zac"."x-y" AS "x-y"'],
            'hyphen aliased'            => [['a' => 'x-y'], '"zac"."x-y" AS "a"'],
            'space'                     => [['my col'], '"zac"."my col" AS "my col"'],
            'space aliased'             => [['a' => 'my col'], '"zac"."my col" AS "a"'],
            'quote character'           => [['a"b'], '"zac"."a\'b" AS "a\'b"'],
            'quote character aliased'   => [['a' => 'a"b'], '"zac"."a\'b" AS "a"'],
            'arithmetic'                => [['price * qty'], '"zac"."price" * "qty" AS "price * qty"'],
            'arithmetic without spaces' => [['a*b'], '"zac"."a"*"b" AS "a*b"'],
            'inline alias'              => [['bar AS baz'], '"zac"."bar" AS "baz" AS "bar AS baz"'],
            'expression'                => [[new Expression('COUNT(*)')], 'COUNT(*) AS Expression1'],
            'expression aliased'        => [['c' => new Expression('COUNT(*)')], 'COUNT(*) AS "c"'],
        ];
    }

    /**
     * @return array<string, array{array<int|string, string|Expression>, bool, string}>
     */
    public static function selectColumnProvider(): array
    {
        return [
            'plain'                             => [['bar'], true, 'SELECT "foo"."bar" AS "bar" FROM "foo"'],
            'plain aliased'                     => [['b' => 'bar'], true, 'SELECT "foo"."bar" AS "b" FROM "foo"'],
            'dotted'                            => [['t.bar'], true, 'SELECT "foo"."t"."bar" AS "t.bar" FROM "foo"'],
            'dotted without prefix'             => [['t.bar'], false, 'SELECT "t"."bar" AS "t.bar" FROM "foo"'],
            'star'                              => [['*'], true, 'SELECT "foo".* FROM "foo"'],
            'star without prefix'               => [['*'], false, 'SELECT * FROM "foo"'],
            'qualified star'                    => [['t.*'], true, 'SELECT "foo"."t".* AS "t.*" FROM "foo"'],
            'qualified star without prefix'     => [['t.*'], false, 'SELECT "t".* AS "t.*" FROM "foo"'],
            'hyphen'                            => [['x-y'], true, 'SELECT "foo"."x-y" AS "x-y" FROM "foo"'],
            'hyphen aliased'                    => [['a' => 'x-y'], true, 'SELECT "foo"."x-y" AS "a" FROM "foo"'],
            'hyphen without prefix'             => [['x-y'], false, 'SELECT "x-y" AS "x-y" FROM "foo"'],
            'space'                             => [['my col'], true, 'SELECT "foo"."my col" AS "my col" FROM "foo"'],
            'space aliased'                     => [['a' => 'my col'], true, 'SELECT "foo"."my col" AS "a" FROM "foo"'],
            'quote character'                   => [['a"b'], true, 'SELECT "foo"."a\'b" AS "a\'b" FROM "foo"'],
            'quote character aliased'           => [['a' => 'a"b'], true, 'SELECT "foo"."a\'b" AS "a" FROM "foo"'],
            'dotted hyphen'                     => [['t-1.x-y'], false, 'SELECT "t-1"."x-y" AS "t-1.x-y" FROM "foo"'],
            'dotted space aliased'              => [
                ['a' => 't 1.my col'],
                false,
                'SELECT "t 1"."my col" AS "a" FROM "foo"',
            ],
            'hyphenated name with inline alias' => [['x-y as z'], false, 'SELECT "x-y" AS "z" FROM "foo"'],
            'padded hyphen'                     => [['x-y '], false, 'SELECT "x-y" AS "x-y " FROM "foo"'],
            'arithmetic'                        => [
                ['price * qty'],
                true,
                'SELECT "foo"."price" * "qty" AS "price * qty" FROM "foo"',
            ],
            'arithmetic without prefix'         => [
                ['price * qty'],
                false,
                'SELECT "price" * "qty" AS "price * qty" FROM "foo"',
            ],
            'arithmetic without spaces'         => [['a*b'], false, 'SELECT "a"*"b" AS "a*b" FROM "foo"'],
            'arithmetic aliased'                => [
                ['total' => 'price * qty'],
                false,
                'SELECT "price" * "qty" AS "total" FROM "foo"',
            ],
            'dotted arithmetic'                 => [
                ['t.price * t.qty'],
                false,
                'SELECT "t"."price" * "t"."qty" AS "t.price * t.qty" FROM "foo"',
            ],
            'inline alias'                      => [['bar AS baz'], true, 'SELECT "foo"."bar" AS "baz" FROM "foo"'],
            'inline alias lower case'           => [['bar as baz'], true, 'SELECT "foo"."bar" as "baz" FROM "foo"'],
            'inline alias without prefix'       => [['t.bar AS baz'], false, 'SELECT "t"."bar" AS "baz" FROM "foo"'],
            'expression'                        => [
                [new Expression('COUNT(id)')],
                true,
                'SELECT COUNT(id) AS Expression1 FROM "foo"',
            ],
            'expression aliased'                => [
                ['c' => new Expression('COUNT(id)')],
                true,
                'SELECT COUNT(id) AS "c" FROM "foo"',
            ],
        ];
    }

    /** @return array<string, array{callable(Select): Select, string}> */
    public static function unquotableColumnProvider(): array
    {
        return [
            'select function call'          => [
                static fn(Select $select): Select => $select->columns(['COUNT(id)']),
                'COUNT(id)',
            ],
            'select arithmetic with hyphen' => [
                static fn(Select $select): Select => $select->columns(['x-y * 2']),
                'x-y * 2',
            ],
            'group function call'           => [
                static fn(Select $select): Select => $select->group('YEAR(created)'),
                'YEAR(created)',
            ],
            'join function call'            => [
                static fn(Select $select): Select => $select->join('zac', 'm = n', ['COUNT(id)']),
                'COUNT(id)',
            ],
        ];
    }

    #[Test]
    #[DataProvider('groupColumnProvider')]
    public function quotesGroupColumnStringsAsIdentifierChains(string|Expression $column, string $expected): void
    {
        $select = (new Select('foo'))->group($column);

        self::assertSame(
            "SELECT \"foo\".* FROM \"foo\" GROUP BY {$expected}",
            $select->getSqlString(new TrustingSql92Platform()),
        );
    }

    /**
     * @param array<int|string, string|Expression> $columns
     */
    #[Test]
    #[DataProvider('joinColumnProvider')]
    public function quotesJoinColumnStringsAsIdentifierChains(array $columns, string $expected): void
    {
        $select = (new Select('foo'))->columns([])->join('zac', 'm = n', $columns);

        self::assertSame(
            "SELECT {$expected} FROM \"foo\" INNER JOIN \"zac\" ON \"m\" = \"n\"",
            $select->getSqlString(new TrustingSql92Platform()),
        );
    }

    /**
     * @param array<int|string, string|Expression> $columns
     */
    #[Test]
    #[DataProvider('selectColumnProvider')]
    public function quotesSelectColumnStringsAsIdentifierChains(
        array $columns,
        bool $prefixColumnsWithTable,
        string $expected,
    ): void {
        $select = (new Select('foo'))->columns($columns, $prefixColumnsWithTable);

        self::assertSame($expected, $select->getSqlString(new TrustingSql92Platform()));
    }

    /** @param callable(Select): Select $build */
    #[Test]
    #[DataProvider('unquotableColumnProvider')]
    public function rejectsColumnStringsHoldingExpressionSyntax(callable $build, string $column): void
    {
        $select = $build(new Select('foo'));

        $this->expectExceptionObject(InvalidArgumentException::forUnquotableIdentifier($column));

        $select->getSqlString(new TrustingSql92Platform());
    }
}
