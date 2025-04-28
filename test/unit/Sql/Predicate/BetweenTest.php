<?php

namespace LaminasTest\Db\Sql\Predicate;

use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\ArgumentType;
use Laminas\Db\Sql\Predicate\Between;
use Override;
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
class BetweenTest extends TestCase
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
        $between    = new Between('foo.bar', 1, 300);
        $identifier = new Argument('foo.bar', ArgumentType::Identifier);
        $minValue   = new Argument(1, ArgumentType::Value);
        $maxValue   = new Argument(300, ArgumentType::Value);

        self::assertEquals($identifier, $between->getIdentifier());
        self::assertEquals($minValue, $between->getMinValue());
        self::assertEquals($maxValue, $between->getMaxValue());

        $between    = new Between('foo.bar', 0, 1);
        $identifier = new Argument('foo.bar', ArgumentType::Identifier);
        $minValue   = new Argument(0, ArgumentType::Value);
        $maxValue   = new Argument(1, ArgumentType::Value);

        self::assertEquals($identifier, $between->getIdentifier());
        self::assertEquals($minValue, $between->getMinValue());
        self::assertEquals($maxValue, $between->getMaxValue());

        $between    = new Between('foo.bar', -1, 0);
        $identifier = new Argument('foo.bar', ArgumentType::Identifier);
        $minValue   = new Argument(-1, ArgumentType::Value);
        $maxValue   = new Argument(0, ArgumentType::Value);

        self::assertEquals($identifier, $between->getIdentifier());
        self::assertEquals($minValue, $between->getMinValue());
        self::assertEquals($maxValue, $between->getMaxValue());
    }

    public function testSpecificationHasSaneDefaultValue(): void
    {
        self::assertEquals('%1$s BETWEEN %2$s AND %3$s', $this->between->getSpecification());
    }

    public function testIdentifierIsMutable(): void
    {
        $this->between->setIdentifier('foo.bar');
        $identifier = new Argument('foo.bar', ArgumentType::Identifier);
        self::assertEquals($identifier, $this->between->getIdentifier());
    }

    public function testMinValueIsMutable(): void
    {
        $this->between->setMinValue(10);
        $expression = new Argument(10, ArgumentType::Value);
        self::assertEquals($expression, $this->between->getMinValue());
    }

    public function testMaxValueIsMutable(): void
    {
        $this->between->setMaxValue(10);
        $expression = new Argument(10, ArgumentType::Value);
        self::assertEquals($expression, $this->between->getMaxValue());
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

        // with parameter
        self::assertEquals($this->between->getSpecification(), $expressionData->getExpressionSpecification());
        self::assertEquals([$identifier, $minValue, $maxValue], $expressionData->getExpressionValues());

        $this->between->setIdentifier([10 => ArgumentType::Value])
                      ->setMinValue(['foo.bar' => ArgumentType::Identifier])
                      ->setMaxValue(['foo.baz' => ArgumentType::Identifier]);

        $identifier = new Argument(10, ArgumentType::Value);
        $minValue   = new Argument('foo.bar', ArgumentType::Identifier);
        $maxValue   = new Argument('foo.baz', ArgumentType::Identifier);

        $expressionData = $this->between->getExpressionData();

        // with parameter
        self::assertEquals($this->between->getSpecification(), $expressionData->getExpressionSpecification());
        self::assertEquals([$identifier, $minValue, $maxValue], $expressionData->getExpressionValues());
    }
}
