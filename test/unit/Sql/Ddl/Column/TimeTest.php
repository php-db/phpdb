<?php

namespace LaminasTest\Db\Sql\Ddl\Column;

use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\Ddl\Column\Time;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Time::class, 'getExpressionData')]
class TimeTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Time('foo');

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s NOT NULL', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('TIME'),
        ], $expressionData->getExpressionValues());
    }
}
