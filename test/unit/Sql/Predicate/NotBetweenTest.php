<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use Override;
use PhpDb\Sql\Argument;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
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

    public function testSpecificationIsNullByDefault(): void
    {
        self::assertNull($this->notBetween->getSpecification());
    }

    public function testRetrievingWherePartsReturnsSpecificationArrayOfIdentifierAndValuesAndArrayOfTypes(): void
    {
        $this->notBetween
            ->setIdentifier('foo.bar')
            ->setMinValue(10)
            ->setMaxValue(19);

        $expressionData = $this->notBetween->getExpressionData();

        // Verify specification (uses new marker format)
        self::assertEquals('{"foo"}.{"bar"} NOT BETWEEN {?} AND {?}', $expressionData['spec']);

        // Verify expression values
        $values = $expressionData['values'];
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

        $this->notBetween
            ->setIdentifier(Argument::value(10))
            ->setMinValue(Argument::identifier('foo.bar'))
            ->setMaxValue(Argument::identifier('foo.baz'));

        $expressionData = $this->notBetween->getExpressionData();

        // Verify specification (uses new marker format)
        self::assertEquals('{?} NOT BETWEEN {"foo"}.{"bar"} AND {"foo"}.{"baz"}', $expressionData['spec']);

        // Verify expression values with custom types
        $values = $expressionData['values'];
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
}
