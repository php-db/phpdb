<?php

namespace LaminasTest\Db\Sql\Predicate;

use PhpDb\Sql\Argument;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Predicate\In;
use PhpDb\Sql\Select;
use PHPUnit\Framework\TestCase;

class InTest extends TestCase
{
    public function testEmptyConstructorYieldsNullIdentifierAndValueSet(): void
    {
        $in = new In();
        self::assertNull($in->getIdentifier());
        self::assertNull($in->getValueSet());
    }

    public function testCanPassIdentifierAndValueSetToConstructor(): void
    {
        $in         = new In('foo.bar', [1, 2]);
        $identifier = new Argument('foo.bar', ArgumentType::Identifier);
        $expression = new Argument([1, 2], ArgumentType::Value);
        self::assertEquals($identifier, $in->getIdentifier());
        self::assertEquals($expression, $in->getValueSet());
    }

    public function testCanPassIdentifierAndEmptyValueSetToConstructor(): void
    {
        $in         = new In('foo.bar', []);
        $identifier = new Argument('foo.bar', ArgumentType::Identifier);
        $expression = new Argument([], ArgumentType::Value);
        $this->assertEquals($identifier, $in->getIdentifier());
        $this->assertEquals($expression, $in->getValueSet());
    }

    public function testIdentifierIsMutable(): void
    {
        $in = new In();
        $in->setIdentifier('foo.bar');
        $identifier = new Argument('foo.bar', ArgumentType::Identifier);
        self::assertEquals($identifier, $in->getIdentifier());
    }

    public function testValueSetIsMutable(): void
    {
        $in = new In();
        $in->setValueSet([1, 2]);
        $expression = new Argument([1, 2], ArgumentType::Value);
        self::assertEquals($expression, $in->getValueSet());
    }

    public function testRetrievingWherePartsReturnsSpecificationArrayOfIdentifierAndValuesAndArrayOfTypes(): void
    {
        $in = new In();
        $in->setIdentifier('foo.bar')
            ->setValueSet([1, 2, 3]);
        $expression1 = new Argument('foo.bar', ArgumentType::Identifier);
        $expression2 = new Argument([1, 2, 3], ArgumentType::Value);

        $expressionData = $in->getExpressionData();

        self::assertEquals('%s IN (%s, %s, %s)', $expressionData->getExpressionSpecification());
        self::assertEquals([$expression1, $expression2], $expressionData->getExpressionValues());

        $in->setIdentifier('foo.bar')
            ->setValueSet([
                [1 => ArgumentType::Literal],
                [2 => ArgumentType::Value],
                [3 => ArgumentType::Literal],
            ]);
        $expression1 = new Argument('foo.bar', ArgumentType::Identifier);
        $expression2 = new Argument([
            [1 => ArgumentType::Literal],
            [2 => ArgumentType::Value],
            [3 => ArgumentType::Literal],
        ], ArgumentType::Value);

        $expressionData = $in->getExpressionData();

        self::assertEquals('%s IN (%s, %s, %s)', $expressionData->getExpressionSpecification());
        self::assertEquals([$expression1, $expression2], $expressionData->getExpressionValues());
    }

    public function testGetExpressionDataWithSubselect(): void
    {
        $select      = new Select();
        $in          = new In(new Argument('foo'), $select);
        $expression1 = new Argument('foo', ArgumentType::Value);
        $expression2 = new Argument($select, ArgumentType::Select);

        $expressionData = $in->getExpressionData();

        self::assertEquals('%s IN %s', $expressionData->getExpressionSpecification());
        self::assertEquals([$expression1, $expression2], $expressionData->getExpressionValues());
    }

    public function testGetExpressionDataWithEmptyValues(): void
    {
        new Select();
        $in = new In('foo', []);

        $expressionData = $in->getExpressionData();

        self::assertEquals('%s IN (NULL)', $expressionData->getExpressionSpecification());
    }

    public function testGetExpressionDataWithSubselectAndIdentifier(): void
    {
        $select      = new Select();
        $in          = new In(new Argument('foo'), $select);
        $expression1 = new Argument('foo', ArgumentType::Value);
        $expression2 = new Argument($select, ArgumentType::Select);

        $expressionData = $in->getExpressionData();

        self::assertEquals('%s IN %s', $expressionData->getExpressionSpecification());
        self::assertEquals([$expression1, $expression2], $expressionData->getExpressionValues());
    }

    public function testGetExpressionDataWithSubselectAndArrayIdentifier(): void
    {
        $select      = new Select();
        $in          = new In(new Argument(['foo', 'bar']), $select);
        $expression1 = new Argument(['foo', 'bar'], ArgumentType::Value);
        $expression2 = new Argument($select, ArgumentType::Select);

        $expressionData = $in->getExpressionData();

        self::assertEquals('(%s, %s) IN %s', $expressionData->getExpressionSpecification());
        self::assertEquals([$expression1, $expression2], $expressionData->getExpressionValues());
    }
}
