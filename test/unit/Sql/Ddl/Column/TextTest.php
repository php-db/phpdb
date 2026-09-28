<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\Text;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Text::class, 'getExpressionData')]
#[CoversMethod(Text::class, 'getLengthExpression')]
#[Group('unit')]
final class TextTest extends TestCase
{
    use ColumnAssertionsTrait;

    #[Test]
    public function getExpressionDataOmitsConfiguredLength(): void
    {
        $expressionData = (new Text('foo', 65_535))->getExpressionData();

        static::assertSame('%s %s NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('TEXT'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function rendersWithoutConfiguredLength(): void
    {
        static::assertColumnRenders('"foo" TEXT NOT NULL', new Text('foo', 65_535));
    }

    public function testGetExpressionData(): void
    {
        $column = new Text('foo');

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s NOT NULL', $expressionData['spec']);
        self::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('TEXT'),
            ],
            $expressionData['values'],
        );
    }
}
