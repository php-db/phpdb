<?php

namespace PhpDbTest\Sql\Predicate;

use Override;
use PhpDb\Sql\Argument;
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
        self::assertEquals(new Argument('foo.bar', ArgumentType::Identifier), $between->getIdentifier());
        self::assertEquals(new Argument(1, ArgumentType::Value), $between->getMinValue());
        self::assertEquals(new Argument(300, ArgumentType::Value), $between->getMaxValue());

        $between = new Between('foo.bar', 0, 1);
        self::assertEquals(new Argument('foo.bar', ArgumentType::Identifier), $between->getIdentifier());
        self::assertEquals(new Argument(0, ArgumentType::Value), $between->getMinValue());
        self::assertEquals(new Argument(1, ArgumentType::Value), $between->getMaxValue());

        $between = new Between('foo.bar', -1, 0);
        self::assertEquals(new Argument('foo.bar', ArgumentType::Identifier), $between->getIdentifier());
        self::assertEquals(new Argument(-1, ArgumentType::Value), $between->getMinValue());
        self::assertEquals(new Argument(0, ArgumentType::Value), $between->getMaxValue());
    }

    public function testSpecificationHasSaneDefaultValue(): void
    {
        self::assertEquals('%1$s BETWEEN %2$s AND %3$s', $this->between->getSpecification());
    }

    public function testIdentifierIsMutable(): void
    {
        $this->between->setIdentifier('foo.bar');
        self::assertEquals(new Argument('foo.bar', ArgumentType::Identifier), $this->between->getIdentifier());
    }

    public function testMinValueIsMutable(): void
    {
        $this->between->setMinValue(10);
        self::assertEquals(new Argument(10, ArgumentType::Value), $this->between->getMinValue());
    }

    public function testMaxValueIsMutable(): void
    {
        $this->between->setMaxValue(10);
        self::assertEquals(new Argument(10, ArgumentType::Value), $this->between->getMaxValue());
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

        $identifier = new Argument('foo.bar', ArgumentType::Identifier);
        $minValue   = new Argument(10, ArgumentType::Value);
        $maxValue   = new Argument(19, ArgumentType::Value);

        $expressionData = $this->between->getExpressionData();

        self::assertEquals($this->between->getSpecification(), $expressionData->getExpressionSpecification());
        self::assertEquals([$identifier, $minValue, $maxValue], $expressionData->getExpressionValues());

        $this->between->setIdentifier([10 => ArgumentType::Value])
                      ->setMinValue(['foo.bar' => ArgumentType::Identifier])
                      ->setMaxValue(['foo.baz' => ArgumentType::Identifier]);

        $identifier = new Argument(10, ArgumentType::Value);
        $minValue   = new Argument('foo.bar', ArgumentType::Identifier);
        $maxValue   = new Argument('foo.baz', ArgumentType::Identifier);

        $expressionData = $this->between->getExpressionData();

        self::assertEquals($this->between->getSpecification(), $expressionData->getExpressionSpecification());
        self::assertEquals([$identifier, $minValue, $maxValue], $expressionData->getExpressionValues());
    }
}
