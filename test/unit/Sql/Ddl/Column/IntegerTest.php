<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument\Argument;
use PhpDb\Sql\Ddl\Column\Column;
use PhpDb\Sql\Ddl\Column\Integer;
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Integer::class, '__construct')]
#[CoversMethod(Column::class, 'getExpressionData')]
final class IntegerTest extends TestCase
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
