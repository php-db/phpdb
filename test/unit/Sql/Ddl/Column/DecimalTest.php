<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument\Argument;
use PhpDb\Sql\Ddl\Column\AbstractPrecisionColumn;
use PhpDb\Sql\Ddl\Column\Decimal;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Decimal::class, 'getExpressionData')]
#[CoversMethod(AbstractPrecisionColumn::class, '__construct')]
#[CoversMethod(AbstractPrecisionColumn::class, 'setDigits')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getDigits')]
#[CoversMethod(AbstractPrecisionColumn::class, 'setDecimal')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getDecimal')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getLengthExpression')]
final class DecimalTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Decimal('foo', 10, 5);

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s(%s) NOT NULL', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('DECIMAL'),
            Argument::literal('10,5'),
        ], $expressionData->getExpressionValues());
    }

    public function testConstructorSetsDigitsAndDecimal(): void
    {
        $column = new Decimal('price', 10, 2);

        self::assertEquals(10, $column->getDigits());
        self::assertEquals(2, $column->getDecimal());
    }

    public function testSetDigitsAndGetDigits(): void
    {
        $column = new Decimal('amount');
        $result = $column->setDigits(15);

        self::assertSame($column, $result); // Fluent interface
        self::assertEquals(15, $column->getDigits());
    }

    public function testSetDecimalAndGetDecimal(): void
    {
        $column = new Decimal('value');
        $result = $column->setDecimal(4);

        self::assertSame($column, $result); // Fluent interface
        self::assertEquals(4, $column->getDecimal());
    }

    public function testGetExpressionDataWithNullDecimal(): void
    {
        $column = new Decimal('amount', 10);
        $column->setDecimal(null);

        $expressionData = $column->getExpressionData();

        // Without decimal, length expression should be just the digits (as string)
        $values = $expressionData->getExpressionValues();
        self::assertCount(3, $values);
        self::assertEquals(Argument::identifier('amount'), $values[0]);
        self::assertEquals(Argument::literal('DECIMAL'), $values[1]);
        // The third value should be "10" (string representation)
        self::assertEquals(Argument::literal((string) 10), $values[2]);
    }

    public function testInheritanceFromAbstractPrecisionColumn(): void
    {
        $column = new Decimal('test');
        self::assertInstanceOf(AbstractPrecisionColumn::class, $column);
    }
}
