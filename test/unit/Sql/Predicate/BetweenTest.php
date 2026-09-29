<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use Override;
use PhpDb\Sql\Argument;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Predicate\Between;
use PhpDb\Sql\Predicate\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function constructorCanPassIdentifierMinimumAndMaximumValues(): void
    {
        $between = new Between('foo.bar', 1, 300);

        $identifier = $between->getIdentifier();
        static::assertInstanceOf(ArgumentInterface::class, $identifier);
        static::assertSame('foo.bar', $identifier->getValue());
        static::assertEquals(ArgumentType::Identifier, $identifier->getType());

        $minValue = $between->getMinValue();
        static::assertInstanceOf(ArgumentInterface::class, $minValue);
        static::assertSame(1, $minValue->getValue());
        static::assertEquals(ArgumentType::Value, $minValue->getType());

        $maxValue = $between->getMaxValue();
        static::assertInstanceOf(ArgumentInterface::class, $maxValue);
        static::assertSame(300, $maxValue->getValue());
        static::assertEquals(ArgumentType::Value, $maxValue->getType());

        $between = new Between('foo.bar', 0, 1);

        $identifier = $between->getIdentifier();
        static::assertInstanceOf(ArgumentInterface::class, $identifier);
        static::assertSame('foo.bar', $identifier->getValue());
        static::assertEquals(ArgumentType::Identifier, $identifier->getType());

        $minValue = $between->getMinValue();
        static::assertInstanceOf(ArgumentInterface::class, $minValue);
        static::assertSame(0, $minValue->getValue());
        static::assertEquals(ArgumentType::Value, $minValue->getType());

        $maxValue = $between->getMaxValue();
        static::assertInstanceOf(ArgumentInterface::class, $maxValue);
        static::assertSame(1, $maxValue->getValue());
        static::assertEquals(ArgumentType::Value, $maxValue->getType());

        $between = new Between('foo.bar', -1, 0);

        $identifier = $between->getIdentifier();
        static::assertInstanceOf(ArgumentInterface::class, $identifier);
        static::assertSame('foo.bar', $identifier->getValue());
        static::assertEquals(ArgumentType::Identifier, $identifier->getType());

        $minValue = $between->getMinValue();
        static::assertInstanceOf(ArgumentInterface::class, $minValue);
        static::assertEquals(-1, $minValue->getValue());
        static::assertEquals(ArgumentType::Value, $minValue->getType());

        $maxValue = $between->getMaxValue();
        static::assertInstanceOf(ArgumentInterface::class, $maxValue);
        static::assertSame(0, $maxValue->getValue());
        static::assertEquals(ArgumentType::Value, $maxValue->getType());
    }

    #[Test]
    public function constructorYieldsNullIdentifierMinimumAndMaximumValues(): void
    {
        static::assertNull($this->between->getIdentifier());
        static::assertNull($this->between->getMinValue());
        static::assertNull($this->between->getMaxValue());
    }

    /**
     * A custom specification replaces the generated one rather than sitting unused
     * behind it.
     */
    #[Test]
    public function getExpressionDataPrefersACustomSpecification(): void
    {
        $between = new Between('foo.bar', 1, 10);
        $between->setSpecification('%1$s IS INBETWEEN %2$s AND %3$s');

        static::assertSame('%1$s IS INBETWEEN %2$s AND %3$s', $between->getExpressionData()['spec']);
    }

    #[Test]
    public function getExpressionDataThrowsExceptionWhenIdentifierNotSet(): void
    {
        $between = new Between();
        $between->setMinValue(1)->setMaxValue(10);

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::MISSING_IDENTIFIER);
        $between->getExpressionData();
    }

    #[Test]
    public function getExpressionDataThrowsExceptionWhenMaxValueNotSet(): void
    {
        $between = new Between();
        $between->setIdentifier('foo')->setMinValue(1);

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::MISSING_MAX_VALUE);
        $between->getExpressionData();
    }

    #[Test]
    public function getExpressionDataThrowsExceptionWhenMinValueNotSet(): void
    {
        $between = new Between();
        $between->setIdentifier('foo')->setMaxValue(10);

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::MISSING_MIN_VALUE);
        $between->getExpressionData();
    }

    #[Test]
    public function identifierIsMutable(): void
    {
        // First mutation
        $result = $this->between->setIdentifier('foo.bar');

        // Verify fluent interface
        static::assertSame($this->between, $result);

        // Verify the first mutation occurred
        $identifier1 = $this->between->getIdentifier();
        static::assertInstanceOf(ArgumentInterface::class, $identifier1);
        static::assertSame('foo.bar', $identifier1->getValue());
        static::assertEquals(ArgumentType::Identifier, $identifier1->getType());

        // Second mutation with different data to verify mutability
        $this->between->setIdentifier('baz.qux');

        // Verify the instance was actually mutated
        $identifier2 = $this->between->getIdentifier();
        static::assertInstanceOf(ArgumentInterface::class, $identifier2);
        static::assertSame('baz.qux', $identifier2->getValue());
        static::assertEquals(ArgumentType::Identifier, $identifier2->getType());
    }

    #[Test]
    public function maxValueIsMutable(): void
    {
        // First mutation
        $result = $this->between->setMaxValue(10);

        // Verify fluent interface
        static::assertSame($this->between, $result);

        // Verify the first mutation occurred
        $maxValue1 = $this->between->getMaxValue();
        static::assertInstanceOf(ArgumentInterface::class, $maxValue1);
        static::assertSame(10, $maxValue1->getValue());
        static::assertEquals(ArgumentType::Value, $maxValue1->getType());

        // Second mutation with different data to verify mutability
        $this->between->setMaxValue(30);

        // Verify the instance was actually mutated
        $maxValue2 = $this->between->getMaxValue();
        static::assertInstanceOf(ArgumentInterface::class, $maxValue2);
        static::assertSame(30, $maxValue2->getValue());
        static::assertEquals(ArgumentType::Value, $maxValue2->getType());
    }

    #[Test]
    public function minValueIsMutable(): void
    {
        // First mutation
        $result = $this->between->setMinValue(10);

        // Verify fluent interface
        static::assertSame($this->between, $result);

        // Verify the first mutation occurred
        $minValue1 = $this->between->getMinValue();
        static::assertInstanceOf(ArgumentInterface::class, $minValue1);
        static::assertSame(10, $minValue1->getValue());
        static::assertEquals(ArgumentType::Value, $minValue1->getType());

        // Second mutation with different data to verify mutability
        $this->between->setMinValue(20);

        // Verify the instance was actually mutated
        $minValue2 = $this->between->getMinValue();
        static::assertInstanceOf(ArgumentInterface::class, $minValue2);
        static::assertSame(20, $minValue2->getValue());
        static::assertEquals(ArgumentType::Value, $minValue2->getType());
    }

    #[Test]
    public function retrievingWherePartsReturnsSpecificationArrayOfIdentifierAndValuesAndArrayOfTypes(): void
    {
        $this->between
            ->setIdentifier('foo.bar')
            ->setMinValue(10)
            ->setMaxValue(19);

        $expressionData = $this->between->getExpressionData();

        // Verify specification (default built from arguments)
        static::assertSame('%s BETWEEN %s AND %s', $expressionData['spec']);

        // Verify expression values
        $values = $expressionData['values'];
        static::assertCount(3, $values);

        // Verify identifier argument
        static::assertInstanceOf(ArgumentInterface::class, $values[0]);
        static::assertSame('foo.bar', $values[0]->getValue());
        static::assertEquals(ArgumentType::Identifier, $values[0]->getType());

        // Verify min value argument
        static::assertInstanceOf(ArgumentInterface::class, $values[1]);
        static::assertSame(10, $values[1]->getValue());
        static::assertEquals(ArgumentType::Value, $values[1]->getType());

        // Verify max value argument
        static::assertInstanceOf(ArgumentInterface::class, $values[2]);
        static::assertSame(19, $values[2]->getValue());
        static::assertEquals(ArgumentType::Value, $values[2]->getType());

        $this->between
            ->setIdentifier(Argument::value(10))
            ->setMinValue(Argument::identifier('foo.bar'))
            ->setMaxValue(Argument::identifier('foo.baz'));

        $expressionData = $this->between->getExpressionData();

        // Verify specification (default built from arguments)
        static::assertSame('%s BETWEEN %s AND %s', $expressionData['spec']);

        // Verify expression values with custom types
        $values = $expressionData['values'];
        static::assertCount(3, $values);

        // Verify identifier argument (passed as Value type)
        static::assertInstanceOf(ArgumentInterface::class, $values[0]);
        static::assertSame(10, $values[0]->getValue());
        static::assertEquals(ArgumentType::Value, $values[0]->getType());

        // Verify min value argument (passed as Identifier type)
        static::assertInstanceOf(ArgumentInterface::class, $values[1]);
        static::assertSame('foo.bar', $values[1]->getValue());
        static::assertEquals(ArgumentType::Identifier, $values[1]->getType());

        // Verify max value argument (passed as Identifier type)
        static::assertInstanceOf(ArgumentInterface::class, $values[2]);
        static::assertSame('foo.baz', $values[2]->getValue());
        static::assertEquals(ArgumentType::Identifier, $values[2]->getType());
    }

    #[Test]
    public function specificationIsMutable(): void
    {
        $this->between->setSpecification('%1$s IS INBETWEEN %2$s AND %3$s');
        static::assertSame('%1$s IS INBETWEEN %2$s AND %3$s', $this->between->getSpecification());
    }

    #[Test]
    public function specificationIsNullByDefault(): void
    {
        static::assertNull($this->between->getSpecification());
    }

    #[Override]
    protected function setUp(): void
    {
        $this->between = new Between();
    }
}
