<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Ddl\Column;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\Ddl\Column\Column;
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
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
#[Group('unit')]
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

        self::assertEquals('%s %s NOT NULL', $expressionData['spec']);
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('INTEGER'),
        ], $expressionData['values']);

        $column->setNullable(true);

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s NULL DEFAULT NULL', $expressionData['spec']);
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('INTEGER'),
        ], $expressionData['values']);

        $column->setDefault('bar');

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s NULL DEFAULT %s', $expressionData['spec']);
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::literal('INTEGER'),
            Argument::value('bar'),
        ], $expressionData['values']);
    }

    public function testSetDefaultWithLiteral(): void
    {
        $column = new Column();
        $column->setName('created_at');

        $literal = new Literal('CURRENT_TIMESTAMP');
        $result  = $column->setDefault($literal);

        self::assertSame($column, $result);
        self::assertSame($literal, $column->getDefault());
    }

    public function testGetExpressionDataWithLiteralDefault(): void
    {
        $column = new Column();
        $column->setName('created_at');
        $column->setDefault(new Literal('CURRENT_TIMESTAMP'));

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s NOT NULL DEFAULT %s', $expressionData['spec']);
        self::assertEquals([
            Argument::identifier('created_at'),
            Argument::literal('INTEGER'),
            Argument::literal('CURRENT_TIMESTAMP'),
        ], $expressionData['values']);
    }

    public function testSetDefaultWithValue(): void
    {
        $column = new Column();
        $column->setName('score');

        $value  = new Value(99);
        $result = $column->setDefault($value);

        self::assertSame($column, $result);
        self::assertSame($value, $column->getDefault());
    }

    public function testGetExpressionDataWithValueDefault(): void
    {
        $column = new Column();
        $column->setName('score');
        $column->setDefault(new Value(42));

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s NOT NULL DEFAULT %s', $expressionData['spec']);
        self::assertEquals([
            Argument::identifier('score'),
            Argument::literal('INTEGER'),
            Argument::value(42),
        ], $expressionData['values']);
    }

    public function testSetDefaultWithFloat(): void
    {
        $column = new Column();
        $column->setName('rate');

        $result = $column->setDefault(3.14);

        self::assertSame($column, $result);
        self::assertSame(3.14, $column->getDefault());
    }

    public function testGetExpressionDataWithFloatDefault(): void
    {
        $column = new Column();
        $column->setName('rate');
        $column->setDefault(9.99);

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s NOT NULL DEFAULT %s', $expressionData['spec']);
        self::assertEquals([
            Argument::identifier('rate'),
            Argument::literal('INTEGER'),
            Argument::value(9.99),
        ], $expressionData['values']);
    }

    public function testSetDefaultWithBool(): void
    {
        $column = new Column();
        $column->setName('is_active');

        $result = $column->setDefault(true);

        self::assertSame($column, $result);
        self::assertTrue($column->getDefault());
    }

    public function testGetExpressionDataWithBoolDefault(): void
    {
        $column = new Column();
        $column->setName('is_active');
        $column->setDefault(false);

        $expressionData = $column->getExpressionData();

        self::assertEquals('%s %s NOT NULL DEFAULT %s', $expressionData['spec']);
        self::assertEquals([
            Argument::identifier('is_active'),
            Argument::literal('INTEGER'),
            Argument::value(false),
        ], $expressionData['values']);
    }

    public function testAddConstraintAppendsConstraintToColumn(): void
    {
        $column = new Column('id');

        $result = $column->addConstraint(new PrimaryKey());

        self::assertSame($column, $result);
    }

    public function testGetExpressionDataIncludesConstraints(): void
    {
        $column = new Column('id');
        $column->addConstraint(new PrimaryKey());

        $expressionData = $column->getExpressionData();

        self::assertStringContainsString('PRIMARY KEY', $expressionData['spec']);
    }

    public function testGetExpressionDataIncludesConstraintValues(): void
    {
        $column = new Column('id');
        $column->addConstraint(new PrimaryKey('id', 'pk_id'));

        $expressionData = $column->getExpressionData();

        self::assertNotEmpty($expressionData['values']);
    }
}
