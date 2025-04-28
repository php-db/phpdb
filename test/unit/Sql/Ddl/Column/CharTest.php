<?php

namespace LaminasTest\Db\Sql\Ddl\Column;

use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\Ddl\Column\Char;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Char::class, 'getExpressionData')]
final class CharTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Char('foo', 20);

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s(%s) NOT NULL', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('CHAR'),
            Argument::literal('20'),
        ], $expressionData->getExpressionValues());
    }
}
