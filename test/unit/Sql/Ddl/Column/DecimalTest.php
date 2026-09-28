<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\AbstractLengthColumn;
use PhpDb\Sql\Ddl\Column\AbstractPrecisionColumn;
use PhpDb\Sql\Ddl\Column\Decimal;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Decimal::class, 'getExpressionData')]
#[CoversMethod(AbstractPrecisionColumn::class, '__construct')]
#[CoversMethod(AbstractPrecisionColumn::class, 'setDigits')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getDigits')]
#[CoversMethod(AbstractPrecisionColumn::class, 'setDecimal')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getDecimal')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getLengthExpression')]
#[CoversMethod(AbstractLengthColumn::class, 'getExpressionData')]
#[Group('unit')]
final class DecimalTest extends TestCase
{
    use ColumnAssertionsTrait;

    #[Test]
    public function getExpressionDataOmitsLengthPlaceholderWhenPrecisionIsNotSet(): void
    {
        $expressionData = (new Decimal('amount'))->getExpressionData();

        static::assertSame('%s %s NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('amount'),
                Argument::literal('DECIMAL'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function rendersDigitsAndDecimalDirectlyAfterType(): void
    {
        static::assertColumnRenders('"price" DECIMAL(10,2) NOT NULL', new Decimal('price', 10, 2));
    }

    #[Test]
    public function rendersDigitsOnlyWhenDecimalIsNotSet(): void
    {
        static::assertColumnRenders('"price" DECIMAL(10) NOT NULL', new Decimal('price', 10));
    }

    #[Test]
    public function rendersWithoutParenthesesWhenPrecisionIsNotSet(): void
    {
        static::assertColumnRenders('"price" DECIMAL NOT NULL', new Decimal('price'));
    }

    public function testConstructorSetsDigitsAndDecimal(): void
    {
        $column = new Decimal('price', 10, 2);

        self::assertEquals(10, $column->getDigits());
        self::assertEquals(2, $column->getDecimal());
    }

    public function testGetExpressionData(): void
    {
        $column = new Decimal('foo', 10, 5);

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s(%s) NOT NULL', $expressionData['spec']);
        self::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('DECIMAL'),
                Argument::literal('10,5'),
            ],
            $expressionData['values'],
        );
    }

    public function testGetExpressionDataWithNullDecimal(): void
    {
        $column = new Decimal('amount', 10);
        $column->setDecimal(null);

        $expressionData = $column->getExpressionData();

        // Without decimal, length expression should be just the digits (as string)
        $values = $expressionData['values'];
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

    public function testSetDecimalAndGetDecimal(): void
    {
        $column = new Decimal('value');
        $result = $column->setDecimal(4);

        self::assertSame($column, $result); // Fluent interface
        self::assertEquals(4, $column->getDecimal());
    }

    public function testSetDigitsAndGetDigits(): void
    {
        $column = new Decimal('amount');
        $result = $column->setDigits(15);

        self::assertSame($column, $result); // Fluent interface
        self::assertEquals(15, $column->getDigits());
    }

    #[Test]
    public function throwsWhenDecimalIsSetWithoutDigits(): void
    {
        $column = new Decimal('price', null, 2);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column "price" of type DECIMAL has a decimal scale but no digits');

        $column->getExpressionData();
    }
}
