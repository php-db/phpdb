<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\Platform\Sql92;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\IdentifierPiece;
use PhpDb\Sql\IdentifierQuoter;
use PhpDb\Sql\IdentifierShape;
use PhpDbTest\TestAsset\TrustingHyphenWordPlatform;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(IdentifierQuoter::class)]
#[CoversClass(IdentifierShape::class)]
#[CoversClass(IdentifierPiece::class)]
#[Group('unit')]
final class IdentifierQuoterTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function emptyPartProvider(): array
    {
        return [
            'empty'        => [''],
            'spaces only'  => ['  '],
            'leading dot'  => ['.a'],
            'trailing dot' => ['a.'],
            'double dot'   => ['a..b'],
            'spaced dots'  => ['x-y. .b'],
        ];
    }

    /** @return array<string, array{string, string}> */
    public static function hyphenWordPlatformProvider(): array
    {
        return [
            'hyphen'            => ['x-y', '`x-y`'],
            'hyphen arithmetic' => ['a-b * c', '`a-b` * `c`'],
            'space'             => ['my col', '`my col`'],
            'qualified space'   => ['t.my col', '`t`.`my col`'],
        ];
    }

    /** @return array<string, array{string, string}> */
    public static function quotedColumnProvider(): array
    {
        return [
            'plain'                  => ['a', '"a"'],
            'fragment alias'         => ['a as b', '"a" as "b"'],
            'hyphenated name alias'  => ['x-y as z', '"x-y" AS "z"'],
            'qualified hyphen alias' => ['t.x-y AS my col', '"t"."x-y" AS "my col"'],
            'hyphen'                 => ['x-y', '"x-y"'],
        ];
    }

    /** @return array<string, array{string, string}> */
    public static function quotedNameProvider(): array
    {
        return [
            'plain'                   => ['a', '"a"'],
            'qualified'               => ['t.a', '"t"."a"'],
            'schema qualified'        => ['s.t.a', '"s"."t"."a"'],
            'star'                    => ['*', '*'],
            'qualified star'          => ['t.*', '"t".*'],
            'multiplication'          => ['price*qty', '"price"*"qty"'],
            'spaced multiplication'   => ['price * qty', '"price" * "qty"'],
            'alias'                   => ['a as b', '"a" as "b"'],
            'trailing space'          => ['a ', '"a" '],
            'hyphen'                  => ['x-y', '"x-y"'],
            'qualified hyphen'        => ['t-1.x-y', '"t-1"."x-y"'],
            'hyphen then star'        => ['x-y.*', '"x-y".*'],
            'space'                   => ['my col', '"my col"'],
            'qualified space'         => ['t.my col', '"t"."my col"'],
            'padded hyphen'           => [' x-y ', '"x-y"'],
            'padded qualified hyphen' => ['t. x-y', '"t"."x-y"'],
            'as after qualifier'      => ['t.as', '"t"."as"'],
            'as alone'                => ['as', '"as"'],
            'non-ascii letter'        => ['café', '"café"'],
            'quote character'         => ['a"b', '"a\'b"'],
        ];
    }

    /** @return array<string, array{string}> */
    public static function unquotableColumnProvider(): array
    {
        return [
            'function call with alias' => ['COUNT(id) as c'],
            'two aliases'              => ['x-y as z as w'],
            'arithmetic'               => ['x-y * 2'],
        ];
    }

    /** @return array<string, array{string}> */
    public static function unquotableNameProvider(): array
    {
        return [
            'function call'              => ['COUNT(id)'],
            'function call of star'      => ['COUNT(*)'],
            'spaced minus'               => ['a - b'],
            'comparison'                 => ['a=b'],
            'hyphen times number'        => ['x-y * 2'],
            'number times hyphen'        => ['price*x-y'],
            'hyphenated name with alias' => ['x-y as z'],
        ];
    }

    #[Test]
    #[DataProvider('hyphenWordPlatformProvider')]
    public function followsThePlatformsOwnWordCharacters(string $name, string $expected): void
    {
        static::assertSame($expected, IdentifierQuoter::quote($name, new TrustingHyphenWordPlatform()));
    }

    #[Test]
    public function leavesNamesUnquotedWhenThePlatformDoesNotQuoteIdentifiers(): void
    {
        $platform = new class extends Sql92 {
            protected bool $quoteIdentifiers = false;
        };

        static::assertSame('x-y.my col', IdentifierQuoter::quote('x-y.my col', $platform));
    }

    #[Test]
    #[DataProvider('quotedNameProvider')]
    public function quotesANameSoItRendersAsWritten(string $name, string $expected): void
    {
        static::assertSame($expected, IdentifierQuoter::quote($name, new TrustingSql92Platform()));
    }

    #[Test]
    public function quotesAPlainNameByFragmentWithoutReadingItsPieces(): void
    {
        $fragment = static fn(string $name): string => "[{$name}]";
        $whole    = static fn(string $name): string => "{{$name}}";
        $platform = $this->createStub(PlatformInterface::class);
        $platform->method('quoteIdentifierInFragment')->willReturnCallback($fragment);
        $platform->method('quoteIdentifier')->willReturnCallback($whole);

        static::assertSame('[t.a]', IdentifierQuoter::quote('t.a', $platform));
    }

    #[Test]
    #[DataProvider('quotedColumnProvider')]
    public function quotesEachSideOfAnInlineColumnAliasWhereTheWholeCannotBe(string $column, string $expected): void
    {
        static::assertSame($expected, IdentifierQuoter::quoteColumn($column, new TrustingSql92Platform()));
    }

    #[Test]
    #[DataProvider('unquotableColumnProvider')]
    public function rejectsAColumnWhoseSidesCannotBeQuoted(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        IdentifierQuoter::quoteColumn($column, new TrustingSql92Platform());
    }

    #[Test]
    #[DataProvider('emptyPartProvider')]
    public function rejectsANameWithAnEmptyPart(string $name): void
    {
        $this->expectExceptionObject(InvalidArgumentException::forEmptyIdentifierPart($name));

        IdentifierQuoter::quote($name, new TrustingSql92Platform());
    }

    #[Test]
    #[DataProvider('unquotableNameProvider')]
    public function rejectsExpressionSyntaxThatNoQuotingRendersAsWritten(string $name): void
    {
        $this->expectExceptionObject(InvalidArgumentException::forUnquotableIdentifier($name));

        IdentifierQuoter::quote($name, new TrustingSql92Platform());
    }

    #[Test]
    public function separatesQualifierPartsWithThePlatformSeparator(): void
    {
        $platform = $this->createStub(PlatformInterface::class);
        $platform->method('quoteIdentifierInFragment')
            ->willReturnCallback(
                static fn(string $name): string => "[{$name}]" === '[x-y]' ? '[x][-][y]' : "[{$name}]",
            );
        $platform->method('quoteIdentifier')->willReturnCallback(static fn(string $name): string => "[{$name}]");
        $platform->method('getIdentifierSeparator')->willReturn('::');

        static::assertSame('[t]::[x-y]', IdentifierQuoter::quote('t.x-y', $platform));
    }
}
