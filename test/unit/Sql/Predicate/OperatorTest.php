<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Select;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Expression;
use PhpDb\Sql\Predicate\Exception\InvalidArgumentException;
use PhpDb\Sql\Predicate\Operator;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Operator::class, '__construct')]
#[CoversMethod(Operator::class, 'getLeft')]
#[CoversMethod(Operator::class, 'setLeft')]
#[CoversMethod(Operator::class, 'getOperator')]
#[CoversMethod(Operator::class, 'setOperator')]
#[CoversMethod(Operator::class, 'getRight')]
#[CoversMethod(Operator::class, 'setRight')]
#[CoversMethod(Operator::class, 'getExpressionData')]
#[Group('unit')]
final class OperatorTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function canPassAllValuesToConstructor(): void
    {
        $operator = new Operator('bar', '>=', 'foo.bar');
        static::assertEquals(Operator::OP_GTE, $operator->getOperator());

        $left = $operator->getLeft();
        static::assertInstanceOf(ArgumentInterface::class, $left);
        static::assertSame('bar', $left->getValue());
        static::assertEquals(ArgumentType::Identifier, $left->getType());

        $right = $operator->getRight();
        static::assertInstanceOf(ArgumentInterface::class, $right);
        static::assertSame('foo.bar', $right->getValue());
        static::assertEquals(ArgumentType::Value, $right->getType());

        $operator = new Operator(new Value('bar'), '>=', new Identifier('foo.bar'));
        static::assertEquals(Operator::OP_GTE, $operator->getOperator());

        $left = $operator->getLeft();
        static::assertInstanceOf(ArgumentInterface::class, $left);
        static::assertSame('bar', $left->getValue());
        static::assertEquals(ArgumentType::Value, $left->getType());

        $right = $operator->getRight();
        static::assertInstanceOf(ArgumentInterface::class, $right);
        static::assertSame('foo.bar', $right->getValue());
        static::assertEquals(ArgumentType::Identifier, $right->getType());

        $operator = new Operator('bar', '>=', 0);

        $right = $operator->getRight();
        static::assertInstanceOf(ArgumentInterface::class, $right);
        static::assertSame(0, $right->getValue());
        static::assertEquals(ArgumentType::Value, $right->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function emptyConstructorYieldsDefaultsForOperatorAndLeftAndRightTypes(): void
    {
        $operator = new Operator();
        static::assertEquals(Operator::OP_EQ, $operator->getOperator());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function emptyConstructorYieldsNullLeftAndRightValues(): void
    {
        $operator = new Operator();
        static::assertNull($operator->getLeft());
        static::assertNull($operator->getRight());
    }

    /**
     * A custom specification replaces the generated one rather than sitting unused
     * behind it.
     */
    #[Test]
    public function getExpressionDataPrefersACustomSpecification(): void
    {
        $operator = new Operator('foo.bar', Operator::OPERATOR_EQUAL_TO, 'baz');
        $operator->setSpecification('%1$s IS NOT DISTINCT FROM %2$s');

        static::assertSame('%1$s IS NOT DISTINCT FROM %2$s', $operator->getExpressionData()['spec']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getExpressionDataThrowsExceptionWhenLeftNotSet(): void
    {
        $operator = new Operator();
        $operator->setRight('value');

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::MISSING_LEFT_EXPRESSION);
        $operator->getExpressionData();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getExpressionDataThrowsExceptionWhenRightNotSet(): void
    {
        $operator = new Operator();
        $operator->setLeft('left');

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::MISSING_RIGHT_EXPRESSION);
        $operator->getExpressionData();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function leftIsMutable(): void
    {
        $operator = new Operator();

        // First mutation
        $result = $operator->setLeft('foo.bar');

        // Verify fluent interface
        static::assertSame($operator, $result);

        // Verify the first mutation occurred
        $left1 = $operator->getLeft();
        static::assertInstanceOf(ArgumentInterface::class, $left1);
        static::assertSame('foo.bar', $left1->getValue());
        static::assertEquals(ArgumentType::Identifier, $left1->getType());

        // Second mutation with different data to verify mutability
        $operator->setLeft('baz.qux');

        // Verify the instance was actually mutated
        $left2 = $operator->getLeft();
        static::assertInstanceOf(ArgumentInterface::class, $left2);
        static::assertSame('baz.qux', $left2->getValue());
        static::assertEquals(ArgumentType::Identifier, $left2->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function operatorIsMutable(): void
    {
        $operator = new Operator();
        $operator->setOperator(Operator::OP_LTE);
        static::assertEquals(Operator::OP_LTE, $operator->getOperator());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function retrievingWherePartsReturnsSpecificationArrayOfLeftAndRightAndArrayOfTypes(): void
    {
        $operator = new Operator();
        $operator->setLeft(new Value('foo'))
            ->setOperator('>=')
            ->setRight(new Identifier('foo.bar'));

        $expressionData = $operator->getExpressionData();

        // Verify specification
        static::assertSame('%s >= %s', $expressionData['spec']);

        // Verify expression values
        $values = $expressionData['values'];
        static::assertCount(2, $values);

        // Verify left argument
        static::assertInstanceOf(ArgumentInterface::class, $values[0]);
        static::assertSame('foo', $values[0]->getValue());
        static::assertEquals(ArgumentType::Value, $values[0]->getType());

        // Verify right argument
        static::assertInstanceOf(ArgumentInterface::class, $values[1]);
        static::assertSame('foo.bar', $values[1]->getValue());
        static::assertEquals(ArgumentType::Identifier, $values[1]->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function rightIsMutable(): void
    {
        $operator = new Operator();

        // First mutation - default type (Value)
        $result = $operator->setRight('bar');

        // Verify fluent interface
        static::assertSame($operator, $result);

        // Verify the first mutation occurred
        $right1 = $operator->getRight();
        static::assertInstanceOf(ArgumentInterface::class, $right1);
        static::assertSame('bar', $right1->getValue());
        static::assertEquals(ArgumentType::Value, $right1->getType());

        // Second mutation - with explicit type (Identifier) using factory
        $operator->setRight(new Identifier('bar'));

        // Verify the instance was actually mutated (same value, different type)
        $right2 = $operator->getRight();
        static::assertInstanceOf(ArgumentInterface::class, $right2);
        static::assertSame('bar', $right2->getValue());
        static::assertEquals(ArgumentType::Identifier, $right2->getType());

        // Third mutation - different value with default type
        $operator->setRight('qux');

        // Verify the instance was mutated again
        $right3 = $operator->getRight();
        static::assertInstanceOf(ArgumentInterface::class, $right3);
        static::assertSame('qux', $right3->getValue());
        static::assertEquals(ArgumentType::Value, $right3->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function setLeftWithExpressionInterfaceWrapsInSelect(): void
    {
        $operator   = new Operator();
        $expression = new Expression('NOW()');

        $operator->setLeft($expression);

        $left = $operator->getLeft();
        static::assertInstanceOf(Select::class, $left);
        static::assertSame(ArgumentType::Select, $left->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function setRightWithExpressionInterfaceWrapsInSelect(): void
    {
        $operator   = new Operator();
        $expression = new Expression('NOW()');

        $operator->setRight($expression);

        $right = $operator->getRight();
        static::assertInstanceOf(Select::class, $right);
        static::assertSame(ArgumentType::Select, $right->getType());
    }
}
