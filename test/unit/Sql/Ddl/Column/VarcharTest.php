<?php

namespace LaminasTest\Db\Sql\Ddl\Column;

use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\Ddl\Column\Varchar;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Varchar::class, 'getExpressionData')]
class VarcharTest extends TestCase
{
    public function testGetExpressionData(): void
    {
        $column = new Varchar('foo', 20);

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s(%s) NOT NULL', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('VARCHAR'),
            Argument::literal('20'),
        ], $expressionData->getExpressionValues());

        $column->setDefault('bar');

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s(%s) NOT NULL DEFAULT %s', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('VARCHAR'),
            Argument::literal('20'),
            Argument::value('bar'),
        ], $expressionData->getExpressionValues());
    }
}
