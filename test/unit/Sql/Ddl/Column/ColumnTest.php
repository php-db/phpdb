<?php

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\Column;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Column::class, 'setName')]
#[CoversMethod(Column::class, 'getName')]
#[CoversMethod(Column::class, 'setNullable')]
#[CoversMethod(Column::class, 'isNullable')]
#[CoversMethod(Column::class, 'setDefault')]
#[CoversMethod(Column::class, 'getDefault')]
#[CoversMethod(Column::class, 'setOptions')]
#[CoversMethod(Column::class, 'setOption')]
#[CoversMethod(Column::class, 'getOptions')]
#[CoversMethod(Column::class, 'getExpressionData')]
final class ColumnTest extends TestCase
{
    public function testSetName(): Column
    {
        $column = new Column();
        self::assertSame($column, $column->setName('foo'));
        return $column;
    }

    #[Depends('testSetName')]
    public function testGetName(Column $column): void
    {
        self::assertEquals('foo', $column->getName());
    }

    public function testSetNullable(): Column
    {
        $column = new Column();
        self::assertSame($column, $column->setNullable(true));
        return $column;
    }

    #[Depends('testSetNullable')]
    public function testIsNullable(Column $column): void
    {
        self::assertTrue($column->isNullable());
        $column->setNullable(false);
        self::assertFalse($column->isNullable());
    }

    public function testSetDefault(): Column
    {
        $column = new Column();
        self::assertSame($column, $column->setDefault('foo bar'));
        return $column;
    }

    #[Depends('testSetDefault')]
    public function testGetDefault(Column $column): void
    {
        self::assertEquals('foo bar', $column->getDefault());
    }

    public function testSetOptions(): Column
    {
        $column = new Column();
        self::assertSame($column, $column->setOptions(['autoincrement' => true]));
        return $column;
    }

    public function testSetOption(): void
    {
        $column = new Column();
        self::assertSame($column, $column->setOption('primary', true));
    }

    #[Depends('testSetOptions')]
    public function testGetOptions(Column $column): void
    {
        self::assertEquals(['autoincrement' => true], $column->getOptions());
    }

    public function testGetExpressionData(): void
    {
        $column = new Column();
        $column->setName('foo');

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s NOT NULL', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('INTEGER'),
        ], $expressionData->getExpressionValues());

        $column->setNullable(true);

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('INTEGER'),
        ], $expressionData->getExpressionValues());

        $column->setDefault('bar');

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s DEFAULT %s', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('INTEGER'),
            Argument::value('bar'),
        ], $expressionData->getExpressionValues());
    }
}
