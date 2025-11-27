<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\Predicate\IsNotNull;
use PhpDb\Sql\Predicate\IsNull;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(IsNull::class, '__construct')]
#[CoversMethod(IsNull::class, 'setIdentifier')]
#[CoversMethod(IsNull::class, 'getIdentifier')]
#[CoversMethod(IsNull::class, 'setSpecification')]
#[CoversMethod(IsNull::class, 'getSpecification')]
#[CoversMethod(IsNull::class, 'getExpressionData')]
final class IsNullTest extends TestCase
{
    public function testEmptyConstructorYieldsNullIdentifier(): void
    {
        $isNotNull = new IsNotNull();
        self::assertNull($isNotNull->getIdentifier());
    }

    public function testSpecificationHasSaneDefaultValue(): void
    {
        $isNotNull = new IsNotNull();
        self::assertEquals('%1$s IS NOT NULL', $isNotNull->getSpecification());
    }

    public function testCanPassIdentifierToConstructor(): void
    {
        $isnull = new IsNotNull('foo.bar');

        // Verify identifier was set correctly
        $identifier = $isnull->getIdentifier();
        self::assertInstanceOf(ArgumentInterface::class, $identifier);
        self::assertEquals('foo.bar', $identifier->getValue());
        self::assertEquals(ArgumentType::Identifier, $identifier->getType());
    }

    public function testIdentifierIsMutable(): void
    {
        $isNotNull = new IsNotNull();

        // First mutation
        $result = $isNotNull->setIdentifier('foo.bar');

        // Verify fluent interface
        self::assertSame($isNotNull, $result);

        // Verify the first mutation occurred
        $identifier1 = $isNotNull->getIdentifier();
        self::assertInstanceOf(ArgumentInterface::class, $identifier1);
        self::assertEquals('foo.bar', $identifier1->getValue());
        self::assertEquals(ArgumentType::Identifier, $identifier1->getType());

        // Second mutation to verify mutability
        $isNotNull->setIdentifier('baz.qux');

        // Verify the instance was actually mutated
        $identifier2 = $isNotNull->getIdentifier();
        self::assertInstanceOf(ArgumentInterface::class, $identifier2);
        self::assertEquals('baz.qux', $identifier2->getValue());
        self::assertEquals(ArgumentType::Identifier, $identifier2->getType());
    }

    public function testSpecificationIsMutable(): void
    {
        $isNotNull = new IsNotNull();
        $isNotNull->setSpecification('%1$s NOT NULL');
        self::assertEquals('%1$s NOT NULL', $isNotNull->getSpecification());
    }

    public function testRetrievingWherePartsReturnsSpecificationArrayOfIdentifierAndArrayOfTypes(): void
    {
        $isNotNull = new IsNotNull();
        $isNotNull->setIdentifier('foo.bar');

        $expressionData = $isNotNull->getExpressionData();

        // Verify specification
        self::assertEquals($isNotNull->getSpecification(), $expressionData->getExpressionSpecification());

        // Verify expression values
        $values = $expressionData->getExpressionValues();
        self::assertCount(1, $values);

        // Verify identifier argument
        self::assertInstanceOf(ArgumentInterface::class, $values[0]);
        self::assertEquals('foo.bar', $values[0]->getValue());
        self::assertEquals(ArgumentType::Identifier, $values[0]->getType());
    }

    public function testGetExpressionDataThrowsExceptionWhenIdentifierNotSet(): void
    {
        $isNull = new IsNull();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier must be specified');
        $isNull->getExpressionData();
    }
}
