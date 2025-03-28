<?php

namespace LaminasTest\Db\Sql\Ddl\Column;

use Laminas\Db\Sql\Ddl\Column\Column;
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
class ColumnTest extends TestCase
{
    public function testSetName(): Column
    {
        $column = new Column();
        self::assertSame($column, $column->setName('foo'));
        return $column;
    }

    #[Depends('testSetName')]
    public function testGetName(Column $column)
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
    public function testIsNullable(Column $column)
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
    public function testGetDefault(Column $column)
    {
        self::assertEquals('foo bar', $column->getDefault());
    }

    public function testSetOptions(): Column
    {
        $column = new Column();
        self::assertSame($column, $column->setOptions(['autoincrement' => true]));
        return $column;
    }

    public function testSetOption()
    {
        $column = new Column();
        self::assertSame($column, $column->setOption('primary', true));
    }

    #[Depends('testSetOptions')]
    public function testGetOptions(Column $column)
    {
        self::assertEquals(['autoincrement' => true], $column->getOptions());
    }

    public function testGetExpressionData()
    {
        $column = new Column();
        $column->setName('foo');
        self::assertEquals(
            [['%s %s NOT NULL', ['foo', 'INTEGER'], [$column::TYPE_IDENTIFIER, $column::TYPE_LITERAL]]],
            $column->getExpressionData()
        );

        $column->setNullable(true);
        self::assertEquals(
            [['%s %s', ['foo', 'INTEGER'], [$column::TYPE_IDENTIFIER, $column::TYPE_LITERAL]]],
            $column->getExpressionData()
        );

        $column->setDefault('bar');
        self::assertEquals(
            [
                [
                    '%s %s DEFAULT %s',
                    ['foo', 'INTEGER', 'bar'],
                    [$column::TYPE_IDENTIFIER, $column::TYPE_LITERAL, $column::TYPE_VALUE],
                ],
            ],
            $column->getExpressionData()
        );
    }
}
