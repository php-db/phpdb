<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use PhpDb\Sql\Argument\Argument;
use PhpDb\Sql\Argument\ArgumentType;
use PhpDb\Sql\Argument\IdentifierArgument;
use PhpDb\Sql\Argument\SelectArgument;
use PhpDb\Sql\Argument\ValueArgument;
use PhpDb\Sql\Argument\ValuesArgument;
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
        $argument = new ValueArgument('test');
        self::assertEquals('test', $argument->getValue());
        self::assertEquals(ArgumentType::Value, $argument->getType());
    }

    public function testConstructorWithExplicitType(): void
    {
        $argument = new IdentifierArgument('column_name');
        self::assertEquals('column_name', $argument->getValue());
        self::assertEquals(ArgumentType::Identifier, $argument->getType());
    }

    public function testConstructorWithExpressionInterface(): void
    {
        $expression = new Expression('NOW()');
        $argument   = new SelectArgument($expression);

        self::assertSame($expression, $argument->getValue());
        self::assertEquals(ArgumentType::Select, $argument->getType());
    }

    public function testConstructorWithSqlInterface(): void
    {
        $select   = new Select();
        $argument = new SelectArgument($select);

        self::assertSame($select, $argument->getValue());
        self::assertEquals(ArgumentType::Select, $argument->getType());
    }

    public function testConstructorThrowsExceptionForInvalidSelectType(): void
    {
        $this->expectException(TypeError::class);
        /** @noinspection PhpParamsInspection */
        /** @noinspection PhpExpressionResultUnusedInspection */
        new SelectArgument('simple_value'); /** @phpstan-ignore-line */
    }

    public function testConstructorWithArrayContainingArgumentType(): void
    {
        $argument = new IdentifierArgument('column');

        self::assertEquals('column', $argument->getValue());
        self::assertEquals(ArgumentType::Identifier, $argument->getType());
    }

    public function testConstructorWithSimpleArray(): void
    {
        $argument = new ValuesArgument([1, 2, 3]);

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
        $argument = new ValueArgument(true);
        self::assertTrue($argument->getValue());
        self::assertEquals(ArgumentType::Value, $argument->getType());
    }

    public function testConstructorWithNullValue(): void
    {
        $argument = new ValueArgument(null);
        self::assertNull($argument->getValue());
        self::assertEquals(ArgumentType::Value, $argument->getType());
    }

    public function testConstructorWithFloatValue(): void
    {
        $argument = new ValueArgument(3.14);
        self::assertEquals(3.14, $argument->getValue());
        self::assertEquals(ArgumentType::Value, $argument->getType());
    }
}
