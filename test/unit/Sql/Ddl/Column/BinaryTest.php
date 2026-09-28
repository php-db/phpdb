<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\AbstractLengthColumn;
use PhpDb\Sql\Ddl\Column\Binary;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Binary::class, 'getExpressionData')]
#[CoversMethod(AbstractLengthColumn::class, 'getExpressionData')]
#[Group('unit')]
final class BinaryTest extends TestCase
{
    use ColumnAssertionsTrait;

    #[Test]
    public function getExpressionDataOmitsLengthPlaceholderWhenLengthIsNotSet(): void
    {
        $expressionData = (new Binary('hash'))->getExpressionData();

        static::assertSame('%s %s NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('hash'),
                Argument::literal('BINARY'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function rendersLengthDirectlyAfterType(): void
    {
        static::assertColumnRenders('"hash" BINARY(32) NOT NULL', new Binary('hash', 32));
    }

    #[Test]
    #[DataProvider('missingLengthProvider')]
    public function rendersWithoutParenthesesWhenLengthIsNotSet(?int $length): void
    {
        static::assertColumnRenders('"hash" BINARY NOT NULL', new Binary('hash', $length));
    }

    public function testGetExpressionData(): void
    {
        $column = new Binary('foo', 10_000_000);

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s(%s) NOT NULL', $expressionData['spec']);
        self::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('BINARY'),
                Argument::literal('10000000'),
            ],
            $expressionData['values'],
        );
    }
}
