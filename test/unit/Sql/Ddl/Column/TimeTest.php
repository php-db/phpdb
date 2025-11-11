<?php

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\Time;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Time::class, 'getExpressionData')]
final class TimeTest extends TestCase
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
