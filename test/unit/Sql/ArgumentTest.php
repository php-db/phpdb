<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use InvalidArgumentException;
use PhpDb\Sql\Argument;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Expression;
use PhpDb\Sql\Select;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

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
        $argument = new Argument('test');
        self::assertEquals('test', $argument->getValue());
        self::assertEquals(ArgumentType::Value, $argument->getType());
    }

    public function testConstructorWithExplicitType(): void
    {
        $argument = new Argument('column_name', ArgumentType::Identifier);
        self::assertEquals('column_name', $argument->getValue());
        self::assertEquals(ArgumentType::Identifier, $argument->getType());
    }

    public function testConstructorWithExpressionInterface(): void
    {
        $expression = new Expression('NOW()');
        $argument   = new Argument($expression);

        self::assertSame($expression, $argument->getValue());
        self::assertEquals(ArgumentType::Select, $argument->getType());
    }

    public function testConstructorWithSqlInterface(): void
    {
        $select   = new Select();
        $argument = new Argument($select);

        self::assertSame($select, $argument->getValue());
        self::assertEquals(ArgumentType::Select, $argument->getType());
    }

    public function testConstructorThrowsExceptionForInvalidSelectType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument value');
        new Argument('simple_value', ArgumentType::Select);
    }

    public function testConstructorWithArrayContainingArgumentType(): void
    {
        $argument = new Argument(['column' => ArgumentType::Identifier]);

        self::assertEquals('column', $argument->getValue());
        self::assertEquals(ArgumentType::Identifier, $argument->getType());
    }

    public function testConstructorWithSimpleArray(): void
    {
        $argument = new Argument([1, 2, 3]);

        self::assertEquals([1, 2, 3], $argument->getValue());
        self::assertEquals(ArgumentType::Value, $argument->getType());
    }

    public function testSetTypeWithArgumentType(): void
    {
        $argument = new Argument('test');
        $result   = $argument->setType(ArgumentType::Literal);

        self::assertSame($argument, $result); // Fluent interface
        self::assertEquals(ArgumentType::Literal, $argument->getType());
    }

    public function testSetTypeWithString(): void
    {
        $argument = new Argument('test');
        $result   = $argument->setType('identifier');

        self::assertSame($argument, $result);
        self::assertEquals(ArgumentType::Identifier, $argument->getType());
    }

    public function testSetTypeThrowsExceptionForInvalidString(): void
    {
        $argument = new Argument('test');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument type');
        $argument->setType('invalid_type');
    }

    public function testSetValue(): void
    {
        $argument = new Argument('initial');
        $result   = $argument->setValue('updated');

        self::assertSame($argument, $result); // Fluent interface
        self::assertEquals('updated', $argument->getValue());
    }

    public function testGetValueAsString(): void
    {
        $argument = new Argument(123);
        self::assertEquals('123', $argument->getValueAsString());

        $argument = new Argument('test');
        self::assertEquals('test', $argument->getValueAsString());
    }

    public function testGetSpecificationWithSimpleValue(): void
    {
        $argument = new Argument('test');
        self::assertEquals('%s', $argument->getSpecification());
    }

    public function testGetSpecificationWithNonEmptyArray(): void
    {
        $argument = new Argument([1, 2, 3]);
        self::assertEquals('(%s, %s, %s)', $argument->getSpecification());
    }

    public function testGetSpecificationWithEmptyArray(): void
    {
        $argument = new Argument([]);
        self::assertEquals('(NULL)', $argument->getSpecification());
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
        $argument = new Argument(true);
        self::assertTrue($argument->getValue());
        self::assertEquals(ArgumentType::Value, $argument->getType());
    }

    public function testConstructorWithNullValue(): void
    {
        $argument = new Argument();
        self::assertNull($argument->getValue());
        self::assertEquals(ArgumentType::Value, $argument->getType());
    }

    public function testConstructorWithFloatValue(): void
    {
        $argument = new Argument(3.14);
        self::assertEquals(3.14, $argument->getValue());
        self::assertEquals(ArgumentType::Value, $argument->getType());
    }
}
