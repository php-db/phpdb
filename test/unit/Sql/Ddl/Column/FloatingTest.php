<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\AbstractLengthColumn;
use PhpDb\Sql\Ddl\Column\AbstractPrecisionColumn;
use PhpDb\Sql\Ddl\Column\Floating;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Floating::class, 'getExpressionData')]
#[CoversMethod(AbstractPrecisionColumn::class, 'getLengthExpression')]
#[CoversMethod(AbstractLengthColumn::class, 'getExpressionData')]
#[Group('unit')]
final class FloatingTest extends TestCase
{
    use ColumnAssertionsTrait;

    #[Test]
    public function rendersDigitsAndDecimalDirectlyAfterType(): void
    {
        static::assertColumnRenders('"f" FLOAT(10,5) NOT NULL', new Floating('f', 10, 5));
    }

    #[Test]
    public function rendersDigitsOnlyWhenDecimalIsNotSet(): void
    {
        static::assertColumnRenders('"f" FLOAT(10) NOT NULL', new Floating('f', 10));
    }

    #[Test]
    public function rendersWithoutParenthesesWhenPrecisionIsNotSet(): void
    {
        static::assertColumnRenders('"f" FLOAT NOT NULL', new Floating('f'));
    }

    public function testGetExpressionData(): void
    {
        $column = new Floating('foo', 10, 5);

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s(%s) NOT NULL', $expressionData['spec']);
        self::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('FLOAT'),
                Argument::literal('10,5'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function throwsWhenDecimalIsSetWithoutDigits(): void
    {
        $column = new Floating('f', null, 2);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column "f" of type FLOAT has a decimal scale but no digits');

        $column->getExpressionData();
    }
}
