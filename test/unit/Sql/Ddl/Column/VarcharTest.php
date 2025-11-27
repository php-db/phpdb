<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\AbstractLengthColumn;
use PhpDb\Sql\Ddl\Column\Varchar;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Varchar::class, 'getExpressionData')]
#[CoversMethod(AbstractLengthColumn::class, '__construct')]
#[CoversMethod(AbstractLengthColumn::class, 'setLength')]
#[CoversMethod(AbstractLengthColumn::class, 'getLength')]
#[CoversMethod(AbstractLengthColumn::class, 'getLengthExpression')]
#[CoversMethod(AbstractLengthColumn::class, 'getExpressionData')]
final class VarcharTest extends TestCase
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

    public function testSetLengthAndGetLength(): void
    {
        $column = new Varchar('name');

        $result = $column->setLength(100);
        self::assertSame($column, $result); // Fluent interface
        self::assertEquals(100, $column->getLength());
    }

    public function testGetExpressionDataWithNullLength(): void
    {
        $column = new Varchar('name');

        $expressionData = $column->getExpressionData();

        // When length is null, getLengthExpression() returns empty string
        // The condition in getExpressionData checks: getLengthExpression() !== '' && !== '0'
        // Empty string fails the first check, so length value is NOT added
        // But specification still has (%s) placeholder - need to verify actual behavior
        $spec   = $expressionData->getExpressionSpecification();
        $values = $expressionData->getExpressionValues();

        // The specification format is defined in AbstractLengthColumn as '%s %s(%s)'
        // But when length value is not added, we need to check if placeholder remains
        self::assertEquals('%s %s(%s) NOT NULL', $spec);
        self::assertEquals([
            Argument::identifier('name'),
            Argument::literal('VARCHAR'),
        ], $values);
    }

    public function testInheritanceFromAbstractLengthColumn(): void
    {
        $column = new Varchar('test');
        self::assertInstanceOf(AbstractLengthColumn::class, $column);
    }
}
