<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Ddl\Column\Column;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Column::class, '__construct')]
#[CoversMethod(Column::class, 'setName')]
#[CoversMethod(Column::class, 'getName')]
#[CoversMethod(Column::class, 'setNullable')]
#[CoversMethod(Column::class, 'isNullable')]
#[CoversMethod(Column::class, 'setDefault')]
#[CoversMethod(Column::class, 'getDefault')]
#[CoversMethod(Column::class, 'setOptions')]
#[CoversMethod(Column::class, 'setOption')]
#[CoversMethod(Column::class, 'getOptions')]
#[CoversMethod(Column::class, 'addConstraint')]
#[CoversMethod(Column::class, 'getExpressionData')]
final class ColumnTest extends TestCase
{
    public function testConstructor(): void
    {
        $column = new Column('test_col', true, 'default_val', ['option1' => 'value1']);
        self::assertEquals('test_col', $column->getName());
        self::assertTrue($column->isNullable());
        self::assertEquals('default_val', $column->getDefault());
        self::assertEquals(['option1' => 'value1'], $column->getOptions());
    }

    public function testSetName(): void
    {
        $column = new Column();

        // First mutation
        $result = $column->setName('foo');

        // Verify fluent interface
        self::assertSame($column, $result);

        // Verify the first mutation occurred
        self::assertEquals('foo', $column->getName());

        // Second mutation to verify mutability
        $column->setName('bar');

        // Verify the instance was actually mutated
        self::assertEquals('bar', $column->getName());
    }

    public function testSetNullable(): void
    {
        $column = new Column();

        // First mutation
        $result = $column->setNullable(true);

        // Verify fluent interface
        self::assertSame($column, $result);

        // Verify the first mutation occurred
        self::assertTrue($column->isNullable());

        // Second mutation to verify mutability
        $column->setNullable(false);

        // Verify the instance was actually mutated
        self::assertFalse($column->isNullable());
    }

    public function testSetDefault(): void
    {
        $column = new Column();

        // First mutation
        $result = $column->setDefault('foo bar');

        // Verify fluent interface
        self::assertSame($column, $result);

        // Verify the first mutation occurred
        self::assertEquals('foo bar', $column->getDefault());

        // Second mutation to verify mutability
        $column->setDefault('baz qux');

        // Verify the instance was actually mutated
        self::assertEquals('baz qux', $column->getDefault());
    }

    public function testSetOptions(): void
    {
        $column = new Column();

        // First mutation
        $result = $column->setOptions(['autoincrement' => true]);

        // Verify fluent interface
        self::assertSame($column, $result);

        // Verify the first mutation occurred
        self::assertEquals(['autoincrement' => true], $column->getOptions());

        // Second mutation to verify mutability
        $column->setOptions(['primary' => true, 'unsigned' => true]);

        // Verify the instance was actually mutated
        self::assertEquals(['primary' => true, 'unsigned' => true], $column->getOptions());
    }

    public function testSetOption(): void
    {
        $column = new Column();

        // First mutation
        $result = $column->setOption('primary', true);

        // Verify fluent interface
        self::assertSame($column, $result);

        // Verify the first mutation occurred
        self::assertEquals(['primary' => true], $column->getOptions());

        // Second mutation to verify mutability
        $column->setOption('unsigned', true);

        // Verify the instance was actually mutated
        self::assertEquals(['primary' => true, 'unsigned' => true], $column->getOptions());
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
