<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Predicate\Exception\InvalidArgumentException;
use PhpDb\Sql\Predicate\IsNotNull;
use PhpDb\Sql\Predicate\IsNull;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(IsNull::class, '__construct')]
#[CoversMethod(IsNull::class, 'setIdentifier')]
#[CoversMethod(IsNull::class, 'getIdentifier')]
#[CoversMethod(IsNull::class, 'setSpecification')]
#[CoversMethod(IsNull::class, 'getSpecification')]
#[CoversMethod(IsNull::class, 'getExpressionData')]
#[Group('unit')]
final class IsNullTest extends TestCase
{
    #[Test]
    public function canPassIdentifierToConstructor(): void
    {
        $isnull = new IsNotNull('foo.bar');

        // Verify identifier was set correctly
        $identifier = $isnull->getIdentifier();
        static::assertInstanceOf(ArgumentInterface::class, $identifier);
        static::assertSame('foo.bar', $identifier->getValue());
        static::assertEquals(ArgumentType::Identifier, $identifier->getType());
    }

    #[Test]
    public function emptyConstructorYieldsNullIdentifier(): void
    {
        $isNotNull = new IsNotNull();
        static::assertNull($isNotNull->getIdentifier());
    }

    /**
     * A custom specification replaces the generated one rather than sitting unused
     * behind it.
     */
    #[Test]
    public function getExpressionDataPrefersACustomSpecification(): void
    {
        $isNotNull = new IsNotNull('foo.bar');
        $isNotNull->setSpecification('%1$s NOT NULL');

        static::assertSame('%1$s NOT NULL', $isNotNull->getExpressionData()['spec']);
    }

    #[Test]
    public function getExpressionDataThrowsExceptionWhenIdentifierNotSet(): void
    {
        $isNull = new IsNull();

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::MISSING_IDENTIFIER);
        $isNull->getExpressionData();
    }

    #[Test]
    public function identifierIsMutable(): void
    {
        $isNotNull = new IsNotNull();

        // First mutation
        $result = $isNotNull->setIdentifier('foo.bar');

        // Verify fluent interface
        static::assertSame($isNotNull, $result);

        // Verify the first mutation occurred
        $identifier1 = $isNotNull->getIdentifier();
        static::assertInstanceOf(ArgumentInterface::class, $identifier1);
        static::assertSame('foo.bar', $identifier1->getValue());
        static::assertEquals(ArgumentType::Identifier, $identifier1->getType());

        // Second mutation to verify mutability
        $isNotNull->setIdentifier('baz.qux');

        // Verify the instance was actually mutated
        $identifier2 = $isNotNull->getIdentifier();
        static::assertInstanceOf(ArgumentInterface::class, $identifier2);
        static::assertSame('baz.qux', $identifier2->getValue());
        static::assertEquals(ArgumentType::Identifier, $identifier2->getType());
    }

    #[Test]
    public function retrievingWherePartsReturnsSpecificationArrayOfIdentifierAndArrayOfTypes(): void
    {
        $isNotNull = new IsNotNull();
        $isNotNull->setIdentifier('foo.bar');

        $expressionData = $isNotNull->getExpressionData();

        // Verify specification (default built from arguments)
        static::assertSame('%s IS NOT NULL', $expressionData['spec']);

        // Verify expression values
        $values = $expressionData['values'];
        static::assertCount(1, $values);

        // Verify identifier argument
        static::assertInstanceOf(ArgumentInterface::class, $values[0]);
        static::assertSame('foo.bar', $values[0]->getValue());
        static::assertEquals(ArgumentType::Identifier, $values[0]->getType());
    }

    #[Test]
    public function setIdentifierWithArgumentInterfacePassesThrough(): void
    {
        $isNull     = new IsNull();
        $identifier = new Identifier('bar');

        $isNull->setIdentifier($identifier);

        static::assertSame($identifier, $isNull->getIdentifier());
    }

    #[Test]
    public function setIdentifierWithStringConvertsToIdentifier(): void
    {
        $isNull = new IsNull();

        $isNull->setIdentifier('foo');

        $identifier = $isNull->getIdentifier();
        static::assertInstanceOf(Identifier::class, $identifier);
        static::assertSame('foo', $identifier->getValue());
    }

    #[Test]
    public function specificationIsMutable(): void
    {
        $isNotNull = new IsNotNull();
        $isNotNull->setSpecification('%1$s NOT NULL');
        static::assertSame('%1$s NOT NULL', $isNotNull->getSpecification());
    }

    #[Test]
    public function specificationIsNullByDefault(): void
    {
        $isNotNull = new IsNotNull();
        static::assertNull($isNotNull->getSpecification());
    }
}
