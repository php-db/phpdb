<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use Closure;
use PhpDb\Sql\AbstractSql;
use PhpDb\Sql\Argument;
use PhpDb\Sql\Delete;
use PhpDb\Sql\Predicate\Expression;
use PhpDb\Sql\Predicate\Literal;
use PhpDb\Sql\Predicate\Predicate;
use PhpDb\Sql\Select;
use PhpDb\Sql\Update;
use PhpDbTest\TestAsset\TrustingHyphenWordPlatform;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[Group('unit')]
#[CoversMethod(AbstractSql::class, 'processExpression')]
#[CoversMethod(AbstractSql::class, 'processIdentifiersArgument')]
#[CoversMethod(AbstractSql::class, 'quoteIdentifierArgument')]
final class PredicateIdentifierNameTest extends TestCase
{
    /**
     * Names whose fragment quoting renders adjacent quoted pieces, and their single-identifier rendering
     *
     * @return array<string, array{string, string}>
     */
    public static function brokenNameProvider(): array
    {
        return [
            'hyphen'                => ['x-y', '"x-y"'],
            'space'                 => ['my col', '"my col"'],
            'quote character'       => ['a"b', '"a\'b"'],
            'non-ascii'             => ['nâme', '"nâme"'],
            'tab'                   => ["a\tb", "\"a\tb\""],
            'dotted hyphen'         => ['t-1.x-y', '"t-1"."x-y"'],
            'dotted space'          => ['t.my col', '"t"."my col"'],
            'hyphen qualified star' => ['t-1.*', '"t-1".*'],
        ];
    }

    /**
     * @return iterable<string, array{Closure(Predicate, string): Predicate, string, string, string}>
     */
    public static function brokenPredicateProvider(): iterable
    {
        foreach (self::predicateMethodProvider() as $method => [$apply, $template]) {
            foreach (self::brokenNameProvider() as $case => [$name, $quoted]) {
                yield "{$method} {$case}" => [$apply, $template, $name, $quoted];
            }
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function hyphenWordPlatformProvider(): array
    {
        return [
            'hyphen'            => ['x-y', '`x-y`'],
            'hyphen arithmetic' => ['a-b * c', '`a-b` * `c`'],
            'dotted hyphen'     => ['t-1.x-y', '`t-1`.`x-y`'],
            'space'             => ['my col', '`my col`'],
            'quote character'   => ['a`b', '`a``b`'],
            'plus'              => ['a+b', '`a+b`'],
        ];
    }

    /**
     * @return array<string, array{Closure(Predicate, string): Predicate, string}>
     */
    public static function predicateMethodProvider(): array
    {
        return [
            'equalTo'     => [static fn(Predicate $p, string $n): Predicate => $p->equalTo($n, 1), "%s = '1'"],
            'notEqualTo'  => [static fn(Predicate $p, string $n): Predicate => $p->notEqualTo($n, 1), "%s != '1'"],
            'lessThan'    => [static fn(Predicate $p, string $n): Predicate => $p->lessThan($n, 1), "%s < '1'"],
            'greaterThan' => [static fn(Predicate $p, string $n): Predicate => $p->greaterThan($n, 1), "%s > '1'"],
            'like'        => [static fn(Predicate $p, string $n): Predicate => $p->like($n, 'a%'), "%s LIKE 'a%%'"],
            'in'          => [static fn(Predicate $p, string $n): Predicate => $p->in($n, [1, 2]), "%s IN ('1', '2')"],
            'between'     => [
                static fn(Predicate $p, string $n): Predicate => $p->between($n, 1, 2),
                "%s BETWEEN '1' AND '2'",
            ],
            'isNull'      => [static fn(Predicate $p, string $n): Predicate => $p->isNull($n), '%s IS NULL'],
        ];
    }

    /**
     * Names whose fragment quoting renders valid SQL, and that rendering
     *
     * @return array<string, array{string, string}>
     */
    public static function unchangedNameProvider(): array
    {
        return [
            'plain'              => ['col', '"col"'],
            'dotted'             => ['t.col', '"t"."col"'],
            'qualified star'     => ['t.*', '"t".*'],
            'arithmetic'         => ['price*qty', '"price"*"qty"'],
            'spaced arithmetic'  => ['a * b', '"a" * "b"'],
            'dotted arithmetic'  => ['t.a * t.b', '"t"."a" * "t"."b"'],
            'inline alias'       => ['x as y', '"x" as "y"'],
            'inline alias upper' => ['x AS y', '"x" AS "y"'],
            'comma'              => ['a,b', '"a,b"'],
            'empty'              => ['', ''],
        ];
    }

    /**
     * @return iterable<string, array{Closure(Predicate, string): Predicate, string, string, string}>
     */
    public static function unchangedPredicateProvider(): iterable
    {
        foreach (self::predicateMethodProvider() as $method => [$apply, $template]) {
            foreach (self::unchangedNameProvider() as $case => [$name, $quoted]) {
                yield "{$method} {$case}" => [$apply, $template, $name, $quoted];
            }
        }
    }

    #[Test]
    #[DataProvider('hyphenWordPlatformProvider')]
    public function followsThePlatformFragmentPatternForWordCharacters(string $name, string $quoted): void
    {
        $select = new Select('foo');
        $select->where->equalTo($name, 1);

        self::assertSame(
            "SELECT `foo`.* FROM `foo` WHERE {$quoted} = '1'",
            $select->getSqlString(new TrustingHyphenWordPlatform()),
        );
    }

    /**
     * @param Closure(Predicate, string): Predicate $apply
     */
    #[Test]
    #[DataProvider('unchangedPredicateProvider')]
    public function keepsValidWhereNameRenderingUnchanged(
        Closure $apply,
        string $template,
        string $name,
        string $quoted,
    ): void {
        $select = new Select('foo');
        $apply($select->where, $name);

        self::assertSame(
            sprintf("SELECT \"foo\".* FROM \"foo\" WHERE {$template}", $quoted),
            $select->getSqlString(new TrustingSql92Platform()),
        );
    }

    #[Test]
    public function leavesExpressionWhereUnchanged(): void
    {
        $select = new Select('foo');
        $select->where(new Expression('x-y = ?', [1]));

        self::assertSame(
            "SELECT \"foo\".* FROM \"foo\" WHERE x-y = '1'",
            $select->getSqlString(new TrustingSql92Platform()),
        );
    }

    #[Test]
    public function leavesLiteralWhereUnchanged(): void
    {
        $select = new Select('foo');
        $select->where(new Literal('x-y = 1'));

        self::assertSame(
            'SELECT "foo".* FROM "foo" WHERE x-y = 1',
            $select->getSqlString(new TrustingSql92Platform()),
        );
    }

    #[Test]
    public function leavesStringWhereUnchanged(): void
    {
        $select = new Select('foo');
        $select->where('x-y = 1');

        self::assertSame(
            'SELECT "foo".* FROM "foo" WHERE x-y = 1',
            $select->getSqlString(new TrustingSql92Platform()),
        );
    }

    #[Test]
    #[DataProvider('brokenNameProvider')]
    #[DataProvider('unchangedNameProvider')]
    public function quotesDeleteWhereName(string $name, string $quoted): void
    {
        $delete = new Delete('foo');
        $delete->where->equalTo($name, 1);

        self::assertSame(
            "DELETE FROM \"foo\" WHERE {$quoted} = '1'",
            $delete->getSqlString(new TrustingSql92Platform()),
        );
    }

    #[Test]
    public function quotesEachNameOfAnIdentifiersArgument(): void
    {
        $select = new Select('foo');
        $select->where(new Expression('(?) IN (SELECT 1)', [Argument::identifiers(['x-y', 't.col', 'a * b'])]));

        self::assertSame(
            'SELECT "foo".* FROM "foo" WHERE ("x-y", "t"."col", "a" * "b") IN (SELECT 1)',
            $select->getSqlString(new TrustingSql92Platform()),
        );
    }

    #[Test]
    #[DataProvider('brokenNameProvider')]
    #[DataProvider('unchangedNameProvider')]
    public function quotesHavingName(string $name, string $quoted): void
    {
        $select = new Select('foo');
        $select->having->greaterThan($name, 1);

        self::assertSame(
            "SELECT \"foo\".* FROM \"foo\" HAVING {$quoted} > '1'",
            $select->getSqlString(new TrustingSql92Platform()),
        );
    }

    /**
     * @param Closure(Predicate, string): Predicate $apply
     */
    #[Test]
    #[DataProvider('brokenPredicateProvider')]
    public function quotesPreviouslyBrokenWhereNameAsOneIdentifier(
        Closure $apply,
        string $template,
        string $name,
        string $quoted,
    ): void {
        $select = new Select('foo');
        $apply($select->where, $name);

        self::assertSame(
            sprintf("SELECT \"foo\".* FROM \"foo\" WHERE {$template}", $quoted),
            $select->getSqlString(new TrustingSql92Platform()),
        );
    }

    #[Test]
    #[DataProvider('brokenNameProvider')]
    #[DataProvider('unchangedNameProvider')]
    public function quotesUpdateWhereName(string $name, string $quoted): void
    {
        $update = new Update('foo');
        $update->set(['bar' => 2]);
        $update->where->equalTo($name, 1);

        self::assertSame(
            "UPDATE \"foo\" SET \"bar\" = '2' WHERE {$quoted} = '1'",
            $update->getSqlString(new TrustingSql92Platform()),
        );
    }

    #[Test]
    #[DataProvider('brokenNameProvider')]
    #[DataProvider('unchangedNameProvider')]
    public function quotesWhereArrayKeyAsPredicateName(string $name, string $quoted): void
    {
        $select = new Select('foo');
        $select->where([$name => 1]);

        self::assertSame(
            "SELECT \"foo\".* FROM \"foo\" WHERE {$quoted} = '1'",
            $select->getSqlString(new TrustingSql92Platform()),
        );
    }

    #[Test]
    #[DataProvider('brokenNameProvider')]
    #[DataProvider('unchangedNameProvider')]
    public function quotesWhereArrayKeyWithNullAsIsNullName(string $name, string $quoted): void
    {
        $select = new Select('foo');
        $select->where([$name => null]);

        self::assertSame(
            "SELECT \"foo\".* FROM \"foo\" WHERE {$quoted} IS NULL",
            $select->getSqlString(new TrustingSql92Platform()),
        );
    }
}
