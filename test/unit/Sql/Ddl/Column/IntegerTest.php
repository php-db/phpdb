<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\Column;
use PhpDb\Sql\Ddl\Column\Integer;
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Integer::class, '__construct')]
#[CoversMethod(Integer::class, 'getExpressionData')]
#[CoversMethod(Column::class, 'getExpressionData')]
#[Group('unit')]
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

        self::assertEquals('%s %s NOT NULL', $expressionData['spec']);
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('INTEGER'),
        ], $expressionData['values']);

        $column = new Integer('foo');
        $column->addConstraint(new PrimaryKey());

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s NOT NULL PRIMARY KEY', $expressionData['spec']);
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('INTEGER'),
        ], $expressionData['values']);
    }

    public function testGetExpressionDataIncludesLengthWhenOptionSet(): void
    {
        $column = new Integer('id');
        $column->setOption('length', '11');

        $expressionData = $column->getExpressionData();

        self::assertStringContainsString('(11)', $expressionData['spec']);
    }

    public function testGetExpressionDataExcludesLengthWhenNotSet(): void
    {
        $column = new Integer('id');

        $expressionData = $column->getExpressionData();

        self::assertStringNotContainsString('(', $expressionData['spec']);
    }
}
