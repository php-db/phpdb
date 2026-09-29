<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\AbstractLengthColumn;
use PhpDb\Sql\Ddl\Column\AbstractPrecisionColumn;
use PhpDb\Sql\Ddl\Column\Double;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[CoversMethod(Double::class, 'getExpressionData')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getLengthExpression')]
#[CoversMethod(AbstractLengthColumn::class, 'getExpressionData')]
#[Group('unit')]
final class DoubleTest extends TestCase
{
    use ColumnAssertionsTrait;

    #[Test]
    public function getExpressionData(): void
    {
        $column = new Double('foo', 10, 5);

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s(%s) NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('DOUBLE'),
                Argument::literal('10,5'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function rendersDigitsAndDecimalDirectlyAfterType(): void
    {
        static::assertColumnRenders('"f" DOUBLE(10,5) NOT NULL', new Double('f', 10, 5));
    }

    #[Test]
    public function rendersDigitsOnlyWhenDecimalIsNotSet(): void
    {
        static::assertColumnRenders('"f" DOUBLE(10) NOT NULL', new Double('f', 10));
    }

    #[Test]
    public function rendersWithoutParenthesesWhenPrecisionIsNotSet(): void
    {
        static::assertColumnRenders('"f" DOUBLE NOT NULL', new Double('f'));
    }

    #[Test]
    public function throwsWhenDecimalIsSetWithoutDigits(): void
    {
        $column = new Double('f', null, 2);

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(sprintf(InvalidArgumentException::MISSING_DECIMAL_DIGITS, 'f', 'DOUBLE'));

        $column->getExpressionData();
    }
}
