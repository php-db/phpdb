<?php

namespace LaminasTest\Db\Sql\Ddl\Column;

use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\Ddl\Column\Datetime;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Datetime::class, 'getExpressionData')]
class DatetimeTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Datetime('foo');

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s NOT NULL', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('DATETIME'),
        ], $expressionData->getExpressionValues());
    }
}
