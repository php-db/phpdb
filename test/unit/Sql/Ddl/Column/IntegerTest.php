<?php

namespace LaminasTest\Db\Sql\Ddl\Column;

use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\Ddl\Column\Column;
use Laminas\Db\Sql\Ddl\Column\Integer;
use Laminas\Db\Sql\Ddl\Constraint\PrimaryKey;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Integer::class, '__construct')]
#[CoversMethod(Column::class, 'getExpressionData')]
class IntegerTest extends TestCase
{
    public function testObjectConstruction(): void
    {
        $integer = new Integer('foo');
        self::assertEquals('foo', $integer->getName());
    }

    public function testGetExpressionData(): void
    {
        $column = new Integer('foo');

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s NOT NULL', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('INTEGER'),
        ], $expressionData->getExpressionValues());

        $column = new Integer('foo');
        $column->addConstraint(new PrimaryKey());

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s NOT NULL PRIMARY KEY', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('INTEGER'),
        ], $expressionData->getExpressionValues());
    }
}
