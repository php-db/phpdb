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
use PHPUnit\Framework\Attributes\Test;
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
    #[Test]
    public function addConstraintAppendsConstraintToColumn(): void
    {
        $column = new Column('id');

        $result = $column->addConstraint(new PrimaryKey());

        static::assertSame($column, $result);
    }

    #[Test]
    public function constructor(): void
    {
        $column = new Column('test_col', true, 'default_val', ['option1' => 'value1']);
        static::assertSame('test_col', $column->getName());
        static::assertTrue($column->isNullable());
        static::assertSame('default_val', $column->getDefault());
        static::assertEquals(['option1' => 'value1'], $column->getOptions());
    }

    #[Test]
    public function getExpressionData(): void
    {
        $column = new Column();
        $column->setName('foo');

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s NOT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('INTEGER'),
            ],
            $expressionData['values'],
        );

        $column->setNullable(true);

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s NULL DEFAULT NULL', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('INTEGER'),
            ],
            $expressionData['values'],
        );

        $column->setDefault('bar');

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s NULL DEFAULT %s', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('foo'),
                Argument::literal('INTEGER'),
                Argument::value('bar'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function getExpressionDataIncludesConstraints(): void
    {
        $column = new Column('id');
        $column->addConstraint(new PrimaryKey());

        $expressionData = $column->getExpressionData();

        static::assertStringContainsString('PRIMARY KEY', $expressionData['spec']);
    }

    #[Test]
    public function getExpressionDataIncludesConstraintValues(): void
    {
        $column = new Column('id');
        $column->addConstraint(new PrimaryKey('id', 'pk_id'));

        $expressionData = $column->getExpressionData();

        static::assertNotEmpty($expressionData['values']);
    }

    #[Test]
    public function getExpressionDataWithBoolDefault(): void
    {
        $column = new Column();
        $column->setName('is_active');
        $column->setDefault(false);

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s NOT NULL DEFAULT %s', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('is_active'),
                Argument::literal('INTEGER'),
                Argument::value(false),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function getExpressionDataWithFloatDefault(): void
    {
        $column = new Column();
        $column->setName('rate');
        $column->setDefault(9.99);

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s NOT NULL DEFAULT %s', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('rate'),
                Argument::literal('INTEGER'),
                Argument::value(9.99),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function getExpressionDataWithLiteralDefault(): void
    {
        $column = new Column();
        $column->setName('created_at');
        $column->setDefault(new Literal('CURRENT_TIMESTAMP'));

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s NOT NULL DEFAULT %s', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('created_at'),
                Argument::literal('INTEGER'),
                Argument::literal('CURRENT_TIMESTAMP'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function getExpressionDataWithValueDefault(): void
    {
        $column = new Column();
        $column->setName('score');
        $column->setDefault(new Value(42));

        $expressionData = $column->getExpressionData();

        static::assertSame('%s %s NOT NULL DEFAULT %s', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::identifier('score'),
                Argument::literal('INTEGER'),
                Argument::value(42),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function setDefault(): void
    {
        $column = new Column();

        // First mutation
        $result = $column->setDefault('foo bar');

        // Verify fluent interface
        static::assertSame($column, $result);

        // Verify the first mutation occurred
        static::assertSame('foo bar', $column->getDefault());

        // Second mutation to verify mutability
        $column->setDefault('baz qux');

        // Verify the instance was actually mutated
        static::assertSame('baz qux', $column->getDefault());
    }

    #[Test]
    public function setDefaultWithBool(): void
    {
        $column = new Column();
        $column->setName('is_active');

        $result = $column->setDefault(true);

        static::assertSame($column, $result);
        static::assertTrue($column->getDefault());
    }

    #[Test]
    public function setDefaultWithFloat(): void
    {
        $column = new Column();
        $column->setName('rate');

        $result = $column->setDefault(3.14);

        static::assertSame($column, $result);
        static::assertSame(3.14, $column->getDefault());
    }

    #[Test]
    public function setDefaultWithLiteral(): void
    {
        $column = new Column();
        $column->setName('created_at');

        $literal = new Literal('CURRENT_TIMESTAMP');
        $result  = $column->setDefault($literal);

        static::assertSame($column, $result);
        static::assertSame($literal, $column->getDefault());
    }

    #[Test]
    public function setDefaultWithValue(): void
    {
        $column = new Column();
        $column->setName('score');

        $value  = new Value(99);
        $result = $column->setDefault($value);

        static::assertSame($column, $result);
        static::assertSame($value, $column->getDefault());
    }

    #[Test]
    public function setName(): void
    {
        $column = new Column();

        // First mutation
        $result = $column->setName('foo');

        // Verify fluent interface
        static::assertSame($column, $result);

        // Verify the first mutation occurred
        static::assertSame('foo', $column->getName());

        // Second mutation to verify mutability
        $column->setName('bar');

        // Verify the instance was actually mutated
        static::assertSame('bar', $column->getName());
    }

    #[Test]
    public function setNullable(): void
    {
        $column = new Column();

        // First mutation
        $result = $column->setNullable(true);

        // Verify fluent interface
        static::assertSame($column, $result);

        // Verify the first mutation occurred
        static::assertTrue($column->isNullable());

        // Second mutation to verify mutability
        $column->setNullable(false);

        // Verify the instance was actually mutated
        static::assertFalse($column->isNullable());
    }

    #[Test]
    public function setOption(): void
    {
        $column = new Column();

        // First mutation
        $result = $column->setOption('primary', true);

        // Verify fluent interface
        static::assertSame($column, $result);

        // Verify the first mutation occurred
        static::assertEquals(['primary' => true], $column->getOptions());

        // Second mutation to verify mutability
        $column->setOption('unsigned', true);

        // Verify the instance was actually mutated
        static::assertEquals(['primary' => true, 'unsigned' => true], $column->getOptions());
    }

    #[Test]
    public function setOptionAcceptsIntegerValue(): void
    {
        $column = new Column();

        $result = $column->setOption('length', 11);

        static::assertSame($column, $result);
        static::assertSame(['length' => 11], $column->getOptions());
    }

    #[Test]
    public function setOptions(): void
    {
        $column = new Column();

        // First mutation
        $result = $column->setOptions(['autoincrement' => true]);

        // Verify fluent interface
        static::assertSame($column, $result);

        // Verify the first mutation occurred
        static::assertEquals(['autoincrement' => true], $column->getOptions());

        // Second mutation to verify mutability
        $column->setOptions(['primary' => true, 'unsigned' => true]);

        // Verify the instance was actually mutated
        static::assertEquals(['primary' => true, 'unsigned' => true], $column->getOptions());
    }
}
