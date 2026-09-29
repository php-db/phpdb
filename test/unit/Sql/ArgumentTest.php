<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Select as ArgumentSelect;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\Argument\Values;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Expression;
use PhpDb\Sql\Select;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use TypeError;

#[CoversMethod(Argument::class, 'value')]
#[CoversMethod(Argument::class, 'identifier')]
#[CoversMethod(Argument::class, 'literal')]
#[CoversMethod(Argument::class, 'select')]
final class ArgumentTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorThrowsExceptionForInvalidSelectType(): void
    {
        self::expectException(TypeError::class);
        /** @noinspection PhpParamsInspection */
        /** @noinspection PhpExpressionResultUnusedInspection */
        new ArgumentSelect('simple_value'); /** @phpstan-ignore-line */
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorWithArrayContainingArgumentType(): void
    {
        $argument = new Identifier('column');

        static::assertSame('column', $argument->getValue());
        static::assertEquals(ArgumentType::Identifier, $argument->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorWithBooleanValue(): void
    {
        $argument = new Value(true);
        static::assertTrue($argument->getValue());
        static::assertEquals(ArgumentType::Value, $argument->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorWithExplicitType(): void
    {
        $argument = new Identifier('column_name');
        static::assertSame('column_name', $argument->getValue());
        static::assertEquals(ArgumentType::Identifier, $argument->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorWithExpressionInterface(): void
    {
        $expression = new Expression('NOW()');
        $argument   = new ArgumentSelect($expression);

        static::assertSame($expression, $argument->getValue());
        static::assertEquals(ArgumentType::Select, $argument->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorWithFloatValue(): void
    {
        $argument = new Value(3.14);
        static::assertSame(3.14, $argument->getValue());
        static::assertEquals(ArgumentType::Value, $argument->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorWithNullValue(): void
    {
        $argument = new Value(null);
        static::assertNull($argument->getValue());
        static::assertEquals(ArgumentType::Value, $argument->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorWithSimpleArray(): void
    {
        $argument = new Values([1, 2, 3]);

        static::assertEquals([1, 2, 3], $argument->getValue());
        static::assertEquals(ArgumentType::Values, $argument->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorWithSimpleValue(): void
    {
        $argument = new Value('test');
        static::assertSame('test', $argument->getValue());
        static::assertEquals(ArgumentType::Value, $argument->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorWithSqlInterface(): void
    {
        $select   = new Select();
        $argument = new ArgumentSelect($select);

        static::assertSame($select, $argument->getValue());
        static::assertEquals(ArgumentType::Select, $argument->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staticIdentifierMethod(): void
    {
        $argument = Argument::identifier('column_name');

        static::assertSame('column_name', $argument->getValue());
        static::assertEquals(ArgumentType::Identifier, $argument->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staticLiteralMethod(): void
    {
        $argument = Argument::literal('LITERAL_VALUE');

        static::assertSame('LITERAL_VALUE', $argument->getValue());
        static::assertEquals(ArgumentType::Literal, $argument->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staticSelectMethodCreatesSelectArgument(): void
    {
        $select   = new Select();
        $argument = Argument::select($select);

        static::assertSame($select, $argument->getValue());
        static::assertEquals(ArgumentType::Select, $argument->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staticValueMethod(): void
    {
        $argument = Argument::value('test_value');

        static::assertSame('test_value', $argument->getValue());
        static::assertEquals(ArgumentType::Value, $argument->getType());
    }
}
