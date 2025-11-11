<?php

namespace LaminasTest\Db\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\Text;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Text::class, 'getExpressionData')]
class TextTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Text('foo');

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s NOT NULL', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('TEXT'),
        ], $expressionData->getExpressionValues());
    }
}
