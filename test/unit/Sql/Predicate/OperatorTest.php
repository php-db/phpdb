<?php

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\Argument;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Predicate\Operator;
use PHPUnit\Framework\TestCase;

class OperatorTest extends TestCase
{
    public function testEmptyConstructorYieldsNullLeftAndRightValues(): void
    {
        $operator = new Operator();
        self::assertNull($operator->getLeft());
        self::assertNull($operator->getRight());
    }

    public function testEmptyConstructorYieldsDefaultsForOperatorAndLeftAndRightTypes(): void
    {
        $operator = new Operator();
        self::assertEquals(Operator::OP_EQ, $operator->getOperator());
    }

    public function testCanPassAllValuesToConstructor(): void
    {
        $operator = new Operator('bar', '>=', 'foo.bar');
        self::assertEquals(Operator::OP_GTE, $operator->getOperator());
        self::assertEquals(new Argument('bar', ArgumentType::Identifier), $operator->getLeft());
        self::assertEquals(new Argument('foo.bar', ArgumentType::Value), $operator->getRight());

        $operator = new Operator(['bar' => ArgumentType::Value], '>=', ['foo.bar' => ArgumentType::Identifier]);
        self::assertEquals(Operator::OP_GTE, $operator->getOperator());
        self::assertEquals(new Argument('bar', ArgumentType::Value), $operator->getLeft());
        self::assertEquals(new Argument('foo.bar', ArgumentType::Identifier), $operator->getRight());

        $operator = new Operator('bar', '>=', 0);
        self::assertEquals(new Argument(0, ArgumentType::Value), $operator->getRight());
    }

    public function testLeftIsMutable(): void
    {
        $operator = new Operator();
        $operator->setLeft('foo.bar');
        $left = new Argument('foo.bar', ArgumentType::Identifier);
        self::assertEquals($left, $operator->getLeft());
    }

    public function testRightIsMutable(): void
    {
        $operator = new Operator();

        $operator->setRight('bar');
        $expression = new Argument('bar', ArgumentType::Value);
        self::assertEquals($expression, $operator->getRight());

        $operator->setRight('bar', ArgumentType::Identifier);
        $expression = new Argument('bar', ArgumentType::Identifier);
        self::assertEquals($expression, $operator->getRight());
    }

    public function testOperatorIsMutable(): void
    {
        $operator = new Operator();
        $operator->setOperator(Operator::OP_LTE);
        self::assertEquals(Operator::OP_LTE, $operator->getOperator());
    }

    public function testRetrievingWherePartsReturnsSpecificationArrayOfLeftAndRightAndArrayOfTypes(): void
    {
        $operator = new Operator();
        $operator
            ->setLeft('foo', ArgumentType::Value)
            ->setOperator('>=')
            ->setRight('foo.bar', ArgumentType::Identifier);

        $left  = new Argument('foo', ArgumentType::Value);
        $right = new Argument('foo.bar', ArgumentType::Identifier);

        $expressionData = $operator->getExpressionData();

        self::assertEquals('%s >= %s', $expressionData->getExpressionSpecification());
        self::assertEquals([$left, $right], $expressionData->getExpressionValues());
    }
}
