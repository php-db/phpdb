<?php

namespace LaminasTest\Db\Sql\Ddl\Column;

use Laminas\Db\Sql\Ddl\Column\Decimal;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Decimal::class, 'getExpressionData')]
class DecimalTest extends TestCase
{
    public function testGetExpressionData()
    {
        $column = new Decimal('foo', 10, 5);
        self::assertEquals(
            [['%s %s NOT NULL', ['foo', 'DECIMAL(10,5)'], [$column::TYPE_IDENTIFIER, $column::TYPE_LITERAL]]],
            $column->getExpressionData()
        );
    }
}
