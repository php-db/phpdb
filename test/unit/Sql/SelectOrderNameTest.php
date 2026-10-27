<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Expression;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Select;
use PhpDbTest\TestAsset\HyphenWordSql92Platform;
use PhpDbTest\TestAsset\TestSql92Platform;
use PhpDbTest\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[CoversMethod(Select::class, 'order')]
#[CoversMethod(Select::class, 'processOrder')]
#[CoversMethod(Select::class, 'quoteOrderIdentifier')]
final class SelectOrderNameTest extends TestCase
{
    /**
     * @return array<string, array{ExpressionInterface|array<int|string, string|ExpressionInterface>|string, string}>
     */
    public static function brokenOrderProvider(): array
    {
        return [
            'hyphen'                    => ['x-y', '"x-y" ASC'],
            'hyphen descending'         => ['x-y DESC', '"x-y" DESC'],
            'dotted hyphen'             => ['t-1.x-y DESC', '"t-1"."x-y" DESC'],
            'quote character'           => ['a"b DESC', '"a\'b" DESC'],
            'non-ascii'                 => ['café DESC', '"café" DESC'],
            'hyphen in comma list'      => ['x-y DESC, b', '"x-y" DESC, "b" ASC'],
            'arithmetic with hyphen'    => ['price*x-y DESC', '"price*x-y" DESC'],
            'hyphen array key'          => [['x-y' => 'DESC'], '"x-y" DESC'],
            'space array key'           => [['my col' => 'DESC'], '"my col" DESC'],
            'dotted space array key'    => [['t.my col' => 'ASC'], '"t"."my col" ASC'],
            'quote character array key' => [['a"b' => 'DESC'], '"a\'b" DESC'],
            'non-ascii array key'       => [['café' => 'DESC'], '"café" DESC'],
            'hyphen array list'         => [['x-y DESC', 'b'], '"x-y" DESC, "b" ASC'],
            'hyphen qualified star'     => ['x-y.* DESC', '"x-y".* DESC'],
        ];
    }

    /**
     * @return array<string, array{PlatformInterface, string, string}>
     */
    public static function platformOrderProvider(): array
    {
        return [
            'hyphen word character'             => [new HyphenWordSql92Platform(), 'x-y DESC', '"x-y" DESC'],
            'hyphen word character arithmetic'  => [
                new HyphenWordSql92Platform(),
                'price*x-y DESC',
                '"price"*"x-y" DESC',
            ],
            'hyphen word character with plus'   => [new HyphenWordSql92Platform(), 'x+y DESC', '"x+y" DESC'],
            'identifier quoting off'            => [
                new TestSql92Platform(quoteIdentifiers: false),
                'x-y DESC',
                'x-y DESC',
            ],
            'identifier quoting off arithmetic' => [
                new TestSql92Platform(quoteIdentifiers: false),
                'price*qty DESC',
                'price*qty DESC',
            ],
        ];
    }

    /**
     * @return array<string, array{ExpressionInterface|array<int|string, string|ExpressionInterface>|string, string}>
     */
    public static function validOrderProvider(): array
    {
        return [
            'plain'                       => ['a', '"a" ASC'],
            'plain descending'            => ['a DESC', '"a" DESC'],
            'lower case descending'       => ['a desc', '"a" DESC'],
            'dotted'                      => ['t.col DESC', '"t"."col" DESC'],
            'comma list'                  => ['a DESC, b', '"a" DESC, "b" ASC'],
            'arithmetic'                  => ['price*qty DESC', '"price"*"qty" DESC'],
            'string with space'           => ['my col DESC', '"my" ASC'],
            'array key'                   => [['a' => 'DESC'], '"a" DESC'],
            'array key unknown direction' => [['a' => 'sideways'], '"a" ASC'],
            'spaced arithmetic key'       => [['price * qty' => 'DESC'], '"price" * "qty" DESC'],
            'dotted arithmetic key'       => [['t.price*t.qty' => 'DESC'], '"t"."price"*"t"."qty" DESC'],
            'trailing space key'          => [['a ' => 'DESC'], '"a"  DESC'],
            'safe word part'              => [['t.as' => 'DESC'], '"t".as DESC'],
            'array list'                  => [['a DESC', 'b'], '"a" DESC, "b" ASC'],
            'expression'                  => [new Expression('RAND()'), 'RAND()'],
            'expression in array'         => [['a', new Expression('RAND()')], '"a" ASC, RAND()'],
        ];
    }

    #[Test]
    #[DataProvider('platformOrderProvider')]
    public function followsThePlatformFragmentRulesForOrderNames(
        PlatformInterface $platform,
        string $order,
        string $expected,
    ): void {
        $select = (new Select('foo'))->order($order);

        self::assertStringEndsWith(" ORDER BY {$expected}", $select->getSqlString($platform));
    }

    /**
     * @param ExpressionInterface|array<int|string, string|ExpressionInterface>|string $order
     */
    #[Test]
    #[DataProvider('brokenOrderProvider')]
    public function quotesOrderNameAsIdentifierChainWhenFragmentQuotingWouldBreakIt(
        ExpressionInterface|array|string $order,
        string $expected,
    ): void {
        self::assertSame("SELECT \"foo\".* FROM \"foo\" ORDER BY {$expected}", $this->render($order));
    }

    /**
     * @param ExpressionInterface|array<int|string, string|ExpressionInterface>|string $order
     */
    #[Test]
    #[DataProvider('validOrderProvider')]
    public function rendersValidOrderUnchanged(ExpressionInterface|array|string $order, string $expected): void
    {
        self::assertSame("SELECT \"foo\".* FROM \"foo\" ORDER BY {$expected}", $this->render($order));
    }

    /**
     * @param ExpressionInterface|array<int|string, string|ExpressionInterface>|string $order
     */
    private function render(ExpressionInterface|array|string $order): string
    {
        return (new Select('foo'))->order($order)
            ->getSqlString(new TrustingSql92Platform());
    }
}
