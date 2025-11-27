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

#[CoversMethod(Argument::class, '__construct')]
#[CoversMethod(Argument::class, 'setType')]
#[CoversMethod(Argument::class, 'getType')]
#[CoversMethod(Argument::class, 'setValue')]
#[CoversMethod(Argument::class, 'getValue')]
#[CoversMethod(Argument::class, 'getValueAsString')]
#[CoversMethod(Argument::class, 'getSpecification')]
#[CoversMethod(Argument::class, 'value')]
#[CoversMethod(Argument::class, 'identifier')]
#[CoversMethod(Argument::class, 'literal')]
final class ArgumentTest extends TestCase
{
    public function testConstructorWithSimpleValue(): void
    {
        $argument = new Value('test');
        self::assertEquals('test', $argument->getValue());
        self::assertEquals(ArgumentType::Value, $argument->getType());
    }

    public function testConstructorWithExplicitType(): void
    {
        $argument = new Identifier('column_name');
        self::assertEquals('column_name', $argument->getValue());
        self::assertEquals(ArgumentType::Identifier, $argument->getType());
    }

    public function testConstructorWithExpressionInterface(): void
    {
        $expression = new Expression('NOW()');
        $argument   = new ArgumentSelect($expression);

        self::assertSame($expression, $argument->getValue());
        self::assertEquals(ArgumentType::Select, $argument->getType());
    }

    public function testConstructorWithSqlInterface(): void
    {
        $select   = new Select();
        $argument = new ArgumentSelect($select);

        self::assertSame($select, $argument->getValue());
        self::assertEquals(ArgumentType::Select, $argument->getType());
    }

    public function testConstructorThrowsExceptionForInvalidSelectType(): void
    {
        $this->expectException(TypeError::class);
        /** @noinspection PhpParamsInspection */
        /** @noinspection PhpExpressionResultUnusedInspection */
        new ArgumentSelect('simple_value'); /** @phpstan-ignore-line */
    }

    public function testConstructorWithArrayContainingArgumentType(): void
    {
        $argument = new Identifier('column');

        self::assertEquals('column', $argument->getValue());
        self::assertEquals(ArgumentType::Identifier, $argument->getType());
    }

    public function testConstructorWithSimpleArray(): void
    {
        $argument = new Values([1, 2, 3]);

        self::assertEquals([1, 2, 3], $argument->getValue());
        self::assertEquals(ArgumentType::Value, $argument->getType());
    }

    public function testStaticValueMethod(): void
    {
        $argument = Argument::value('test_value');

        self::assertEquals('test_value', $argument->getValue());
        self::assertEquals(ArgumentType::Value, $argument->getType());
    }

    public function testStaticIdentifierMethod(): void
    {
        $argument = Argument::identifier('column_name');

        self::assertEquals('column_name', $argument->getValue());
        self::assertEquals(ArgumentType::Identifier, $argument->getType());
    }

    public function testStaticLiteralMethod(): void
    {
        $argument = Argument::literal('LITERAL_VALUE');

        self::assertEquals('LITERAL_VALUE', $argument->getValue());
        self::assertEquals(ArgumentType::Literal, $argument->getType());
    }

    public function testConstructorWithBooleanValue(): void
    {
        $argument = new Value(true);
        self::assertTrue($argument->getValue());
        self::assertEquals(ArgumentType::Value, $argument->getType());
    }

    public function testConstructorWithNullValue(): void
    {
        $argument = new Value(null);
        self::assertNull($argument->getValue());
        self::assertEquals(ArgumentType::Value, $argument->getType());
    }

    public function testConstructorWithFloatValue(): void
    {
        $argument = new Value(3.14);
        self::assertEquals(3.14, $argument->getValue());
        self::assertEquals(ArgumentType::Value, $argument->getType());
    }
}
