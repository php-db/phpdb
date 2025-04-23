<?php

namespace LaminasTest\Db\Sql\Ddl\Column;

use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\Ddl\Column\Timestamp;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Timestamp::class, 'getExpressionData')]
final class TimestampTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Timestamp('foo');

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s NOT NULL', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('TIMESTAMP')
        ], $expressionData->getExpressionValues());
    }
}
