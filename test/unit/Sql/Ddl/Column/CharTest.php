<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Ddl\Column\AbstractLengthColumn;
use PhpDb\Sql\Ddl\Column\Char;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Char::class, 'getExpressionData')]
#[CoversMethod(AbstractLengthColumn::class, 'getExpressionData')]
#[Group('unit')]
final class CharTest extends TestCase
{
    use ColumnAssertionsTrait;

    #[Test]
    public function getExpressionDataOmitsLengthPlaceholderWhenLengthIsNotSet(): void
    {
        $expressionData = (new Char('code'))->getExpressionData();

        static::assertSame('%s %s NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                new Identifier('code'),
                new Literal('CHAR'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function rendersLengthDirectlyAfterType(): void
    {
        static::assertColumnRenders('"code" CHAR(2) NOT NULL', new Char('code', 2));
    }

    #[Test]
    #[DataProvider('missingLengthProvider')]
    public function rendersWithoutParenthesesWhenLengthIsNotSet(?int $length): void
    {
        static::assertColumnRenders('"code" CHAR NOT NULL', new Char('code', $length));
    }

    public function testGetExpressionData(): void
    {
        $column = new Char('foo', 20);

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s(%s) NOT NULL', $expressionData['spec']);
        self::assertEquals(
            [
                new Identifier('foo'),
                new Literal('CHAR'),
                new Literal('20'),
            ],
            $expressionData['values'],
        );
    }
}
