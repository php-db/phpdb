<?php

namespace PhpDbTest\Sql\Predicate;

use Override;
use PhpDb\Sql\Argument;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Predicate\NotBetween;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(NotBetween::class, 'getSpecification')]
#[CoversMethod(NotBetween::class, 'getExpressionData')]
final class NotBetweenTest extends TestCase
{
    protected NotBetween $notBetween;

    #[Override]
    protected function setUp(): void
    {
        $this->notBetween = new NotBetween();
    }

    public function testSpecificationHasSameDefaultValue(): void
    {
        self::assertEquals('%1$s NOT BETWEEN %2$s AND %3$s', $this->notBetween->getSpecification());
    }

    public function testRetrievingWherePartsReturnsSpecificationArrayOfIdentifierAndValuesAndArrayOfTypes(): void
    {
        $this->notBetween
            ->setIdentifier('foo.bar')
            ->setMinValue(10)
            ->setMaxValue(19);

        $identifier = new Argument('foo.bar', ArgumentType::Identifier);
        $minValue   = new Argument(10, ArgumentType::Value);
        $maxValue   = new Argument(19, ArgumentType::Value);

        $expressionData = $this->notBetween->getExpressionData();

        self::assertEquals($this->notBetween->getSpecification(), $expressionData->getExpressionSpecification());
        self::assertEquals([$identifier, $minValue, $maxValue], $expressionData->getExpressionValues());

        $this->notBetween
            ->setIdentifier([10 => ArgumentType::Value])
            ->setMinValue(['foo.bar' => ArgumentType::Identifier])
            ->setMaxValue(['foo.baz' => ArgumentType::Identifier]);

        $identifier = new Argument(10, ArgumentType::Value);
        $minValue   = new Argument('foo.bar', ArgumentType::Identifier);
        $maxValue   = new Argument('foo.baz', ArgumentType::Identifier);

        $expressionData = $this->notBetween->getExpressionData();

        self::assertEquals($this->notBetween->getSpecification(), $expressionData->getExpressionSpecification());
        self::assertEquals([$identifier, $minValue, $maxValue], $expressionData->getExpressionValues());
    }
}
