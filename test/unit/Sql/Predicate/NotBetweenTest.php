<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use Override;
use PhpDb\Sql\Argument;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Predicate\NotBetween;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(NotBetween::class, 'getSpecification')]
#[CoversMethod(NotBetween::class, 'getExpressionData')]
final class NotBetweenTest extends TestCase
{
    protected NotBetween $notBetween;

    #[Test]
    public function retrievingWherePartsReturnsSpecificationArrayOfIdentifierAndValuesAndArrayOfTypes(): void
    {
        $this->notBetween
            ->setIdentifier('foo.bar')
            ->setMinValue(10)
            ->setMaxValue(19);

        $expressionData = $this->notBetween->getExpressionData();

        // Verify specification (default built from arguments)
        static::assertSame('%s NOT BETWEEN %s AND %s', $expressionData['spec']);

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

        $this->notBetween
            ->setIdentifier(Argument::value(10))
            ->setMinValue(Argument::identifier('foo.bar'))
            ->setMaxValue(Argument::identifier('foo.baz'));

        $expressionData = $this->notBetween->getExpressionData();

        // Verify specification (default built from arguments)
        static::assertSame('%s NOT BETWEEN %s AND %s', $expressionData['spec']);

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
    public function specificationIsNullByDefault(): void
    {
        static::assertNull($this->notBetween->getSpecification());
    }

    #[Override]
    protected function setUp(): void
    {
        $this->notBetween = new NotBetween();
    }
}
