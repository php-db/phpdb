<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\Argument;
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
        new IsNotNull();
        $isnull     = new IsNotNull('foo.bar');
        $identifier = new Argument('foo.bar', ArgumentType::Identifier);
        self::assertEquals($identifier, $isnull->getIdentifier());
    }

    public function testIdentifierIsMutable(): void
    {
        $isNotNull = new IsNotNull();
        $isNotNull->setIdentifier('foo.bar');
        $identifier = new Argument('foo.bar', ArgumentType::Identifier);
        self::assertEquals($identifier, $isNotNull->getIdentifier());
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
        $identifier = new Argument('foo.bar', ArgumentType::Identifier);

        $expressionData = $isNotNull->getExpressionData();

        self::assertEquals($isNotNull->getSpecification(), $expressionData->getExpressionSpecification());
        self::assertEquals([$identifier], $expressionData->getExpressionValues());
    }

    public function testGetExpressionDataThrowsExceptionWhenIdentifierNotSet(): void
    {
        $isNull = new IsNull();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier must be specified');
        $isNull->getExpressionData();
    }
}
