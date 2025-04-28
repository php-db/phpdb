<?php

namespace LaminasTest\Db\Sql\Ddl\Column;

use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\Ddl\Column\Varbinary;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Varbinary::class, 'getExpressionData')]
class VarbinaryTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Varbinary('foo', 20);

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s(%s) NOT NULL', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('VARBINARY'),
            Argument::literal('20'),
        ], $expressionData->getExpressionValues());
    }
}
