<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use LogicException;
use Override;
use PhpDb\Sql\Argument;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Predicate\Between;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Between::class, '__construct')]
#[CoversMethod(Between::class, 'getIdentifier')]
#[CoversMethod(Between::class, 'getMinValue')]
#[CoversMethod(Between::class, 'getMaxValue')]
#[CoversMethod(Between::class, 'getSpecification')]
#[CoversMethod(Between::class, 'setIdentifier')]
#[CoversMethod(Between::class, 'setMinValue')]
#[CoversMethod(Between::class, 'setMaxValue')]
#[CoversMethod(Between::class, 'setSpecification')]
#[CoversMethod(Between::class, 'getExpressionData')]
final class BetweenTest extends TestCase
{
    protected Between $between;

    #[Override]
    protected function setUp(): void
    {
        $this->between = new Between();
    }

    public function testConstructorYieldsNullIdentifierMinimumAndMaximumValues(): void
    {
        self::assertNull($this->between->getIdentifier());
        self::assertNull($this->between->getMinValue());
        self::assertNull($this->between->getMaxValue());
    }

    public function testConstructorCanPassIdentifierMinimumAndMaximumValues(): void
    {
        $between = new Between('foo.bar', 1, 300);

        $identifier = $between->getIdentifier();
        self::assertInstanceOf(ArgumentInterface::class, $identifier);
        self::assertEquals('foo.bar', $identifier->getValue());
        self::assertEquals(ArgumentType::Identifier, $identifier->getType());

        $minValue = $between->getMinValue();
        self::assertInstanceOf(ArgumentInterface::class, $minValue);
        self::assertEquals(1, $minValue->getValue());
        self::assertEquals(ArgumentType::Value, $minValue->getType());

        $maxValue = $between->getMaxValue();
        self::assertInstanceOf(ArgumentInterface::class, $maxValue);
        self::assertEquals(300, $maxValue->getValue());
        self::assertEquals(ArgumentType::Value, $maxValue->getType());

        $between = new Between('foo.bar', 0, 1);

        $identifier = $between->getIdentifier();
        self::assertInstanceOf(ArgumentInterface::class, $identifier);
        self::assertEquals('foo.bar', $identifier->getValue());
        self::assertEquals(ArgumentType::Identifier, $identifier->getType());

        $minValue = $between->getMinValue();
        self::assertInstanceOf(ArgumentInterface::class, $minValue);
        self::assertEquals(0, $minValue->getValue());
        self::assertEquals(ArgumentType::Value, $minValue->getType());

        $maxValue = $between->getMaxValue();
        self::assertInstanceOf(ArgumentInterface::class, $maxValue);
        self::assertEquals(1, $maxValue->getValue());
        self::assertEquals(ArgumentType::Value, $maxValue->getType());

        $between = new Between('foo.bar', -1, 0);

        $identifier = $between->getIdentifier();
        self::assertInstanceOf(ArgumentInterface::class, $identifier);
        self::assertEquals('foo.bar', $identifier->getValue());
        self::assertEquals(ArgumentType::Identifier, $identifier->getType());

        $minValue = $between->getMinValue();
        self::assertInstanceOf(ArgumentInterface::class, $minValue);
        self::assertEquals(-1, $minValue->getValue());
        self::assertEquals(ArgumentType::Value, $minValue->getType());

        $maxValue = $between->getMaxValue();
        self::assertInstanceOf(ArgumentInterface::class, $maxValue);
        self::assertEquals(0, $maxValue->getValue());
        self::assertEquals(ArgumentType::Value, $maxValue->getType());
    }

    public function testSpecificationHasSaneDefaultValue(): void
    {
        self::assertEquals('%1$s BETWEEN %2$s AND %3$s', $this->between->getSpecification());
    }

    public function testIdentifierIsMutable(): void
    {
        // First mutation
        $result = $this->between->setIdentifier('foo.bar');

        // Verify fluent interface
        self::assertSame($this->between, $result);

        // Verify the first mutation occurred
        $identifier1 = $this->between->getIdentifier();
        self::assertInstanceOf(ArgumentInterface::class, $identifier1);
        self::assertEquals('foo.bar', $identifier1->getValue());
        self::assertEquals(ArgumentType::Identifier, $identifier1->getType());

        // Second mutation with different data to verify mutability
        $this->between->setIdentifier('baz.qux');

        // Verify the instance was actually mutated
        $identifier2 = $this->between->getIdentifier();
        self::assertInstanceOf(ArgumentInterface::class, $identifier2);
        self::assertEquals('baz.qux', $identifier2->getValue());
        self::assertEquals(ArgumentType::Identifier, $identifier2->getType());
    }

    public function testMinValueIsMutable(): void
    {
        // First mutation
        $result = $this->between->setMinValue(10);

        // Verify fluent interface
        self::assertSame($this->between, $result);

        // Verify the first mutation occurred
        $minValue1 = $this->between->getMinValue();
        self::assertInstanceOf(ArgumentInterface::class, $minValue1);
        self::assertEquals(10, $minValue1->getValue());
        self::assertEquals(ArgumentType::Value, $minValue1->getType());

        // Second mutation with different data to verify mutability
        $this->between->setMinValue(20);

        // Verify the instance was actually mutated
        $minValue2 = $this->between->getMinValue();
        self::assertInstanceOf(ArgumentInterface::class, $minValue2);
        self::assertEquals(20, $minValue2->getValue());
        self::assertEquals(ArgumentType::Value, $minValue2->getType());
    }

    public function testMaxValueIsMutable(): void
    {
        // First mutation
        $result = $this->between->setMaxValue(10);

        // Verify fluent interface
        self::assertSame($this->between, $result);

        // Verify the first mutation occurred
        $maxValue1 = $this->between->getMaxValue();
        self::assertInstanceOf(ArgumentInterface::class, $maxValue1);
        self::assertEquals(10, $maxValue1->getValue());
        self::assertEquals(ArgumentType::Value, $maxValue1->getType());

        // Second mutation with different data to verify mutability
        $this->between->setMaxValue(30);

        // Verify the instance was actually mutated
        $maxValue2 = $this->between->getMaxValue();
        self::assertInstanceOf(ArgumentInterface::class, $maxValue2);
        self::assertEquals(30, $maxValue2->getValue());
        self::assertEquals(ArgumentType::Value, $maxValue2->getType());
    }

    public function testSpecificationIsMutable(): void
    {
        $this->between->setSpecification('%1$s IS INBETWEEN %2$s AND %3$s');
        self::assertEquals('%1$s IS INBETWEEN %2$s AND %3$s', $this->between->getSpecification());
    }

    public function testRetrievingWherePartsReturnsSpecificationArrayOfIdentifierAndValuesAndArrayOfTypes(): void
    {
        $this->between->setIdentifier('foo.bar')
                      ->setMinValue(10)
                      ->setMaxValue(19);

        $expressionData = $this->between->getExpressionData();

        // Verify specification
        self::assertEquals($this->between->getSpecification(), $expressionData->getExpressionSpecification());

        // Verify expression values
        $values = $expressionData->getExpressionValues();
        self::assertCount(3, $values);

        // Verify identifier argument
        self::assertInstanceOf(ArgumentInterface::class, $values[0]);
        self::assertEquals('foo.bar', $values[0]->getValue());
        self::assertEquals(ArgumentType::Identifier, $values[0]->getType());

        // Verify min value argument
        self::assertInstanceOf(ArgumentInterface::class, $values[1]);
        self::assertEquals(10, $values[1]->getValue());
        self::assertEquals(ArgumentType::Value, $values[1]->getType());

        // Verify max value argument
        self::assertInstanceOf(ArgumentInterface::class, $values[2]);
        self::assertEquals(19, $values[2]->getValue());
        self::assertEquals(ArgumentType::Value, $values[2]->getType());

        $this->between->setIdentifier(Argument::value(10))
                      ->setMinValue(Argument::identifier('foo.bar'))
                      ->setMaxValue(Argument::identifier('foo.baz'));

        $expressionData = $this->between->getExpressionData();

        // Verify specification
        self::assertEquals($this->between->getSpecification(), $expressionData->getExpressionSpecification());

        // Verify expression values with custom types
        $values = $expressionData->getExpressionValues();
        self::assertCount(3, $values);

        // Verify identifier argument (passed as Value type)
        self::assertInstanceOf(ArgumentInterface::class, $values[0]);
        self::assertEquals(10, $values[0]->getValue());
        self::assertEquals(ArgumentType::Value, $values[0]->getType());

        // Verify min value argument (passed as Identifier type)
        self::assertInstanceOf(ArgumentInterface::class, $values[1]);
        self::assertEquals('foo.bar', $values[1]->getValue());
        self::assertEquals(ArgumentType::Identifier, $values[1]->getType());

        // Verify max value argument (passed as Identifier type)
        self::assertInstanceOf(ArgumentInterface::class, $values[2]);
        self::assertEquals('foo.baz', $values[2]->getValue());
        self::assertEquals(ArgumentType::Identifier, $values[2]->getType());
    }

    public function testGetExpressionDataThrowsExceptionWhenIdentifierNotSet(): void
    {
        $between = new Between();
        $between->setMinValue(1)->setMaxValue(10);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Identifier must be specified');
        $between->getExpressionData();
    }

    public function testGetExpressionDataThrowsExceptionWhenMinValueNotSet(): void
    {
        $between = new Between();
        $between->setIdentifier('foo')->setMaxValue(10);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('minValue must be specified');
        $between->getExpressionData();
    }

    public function testGetExpressionDataThrowsExceptionWhenMaxValueNotSet(): void
    {
        $between = new Between();
        $between->setIdentifier('foo')->setMinValue(1);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('maxValue must be specified');
        $between->getExpressionData();
    }
}
