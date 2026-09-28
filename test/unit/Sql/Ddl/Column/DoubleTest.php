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

#[CoversMethod(Double::class, 'getExpressionData')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getLengthExpression')]
#[CoversMethod(AbstractLengthColumn::class, 'getExpressionData')]
#[Group('unit')]
final class DoubleTest extends TestCase
{
    use ColumnAssertionsTrait;

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

    public function testGetExpressionData(): void
    {
        $column = new Double('foo', 10, 5);

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s(%s) NOT NULL', $expressionData['spec']);
        self::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('DOUBLE'),
                Argument::literal('10,5'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function throwsWhenDecimalIsSetWithoutDigits(): void
    {
        $column = new Double('f', null, 2);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column "f" of type DOUBLE has a decimal scale but no digits');

        $column->getExpressionData();
    }
}
