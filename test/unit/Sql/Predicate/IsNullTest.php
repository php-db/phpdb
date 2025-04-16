<?php

namespace LaminasTest\Db\Sql\Predicate;

use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\ArgumentType;
use Laminas\Db\Sql\Predicate\IsNotNull;
use PHPUnit\Framework\TestCase;

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
        $isnull = new IsNotNull('foo.bar');
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
        $expected = [
            [
                $isNotNull->getSpecification(),
                [$identifier],
            ],
        ];
        self::assertEquals($expected, $isNotNull->getExpressionData());
    }
}
