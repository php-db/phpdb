<?php

namespace LaminasTest\Db\Sql\Ddl\Column;

use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\Ddl\Column\Decimal;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Decimal::class, 'getExpressionData')]
class DecimalTest extends TestCase
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
}
