<?php

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\Argument;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Predicate\Expression;
use PhpDb\Sql\Predicate\IsNull;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

final class ExpressionTest extends TestCase
{
    public function testEmptyConstructorYieldsEmptyLiteralAndParameter(): void
    {
        $expression = new Expression();
        self::assertEquals('', $expression->getExpression());
        self::assertEmpty($expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassLiteralAndSingleScalarParameterToConstructor(): void
    {
        $expression = new Expression('foo.bar = ?', 'bar');
        $bar        = new Argument('bar', ArgumentType::Value);
        self::assertEquals('foo.bar = ?', $expression->getExpression());
        self::assertEquals([$bar], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassNoParameterToConstructor(): void
    {
        $expression = new Expression('foo.bar');
        self::assertEquals([], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassSingleNullParameterToConstructor(): void
    {
        $expression = new Expression('?', null);
        $null       = new Argument(null, ArgumentType::Value);
        self::assertEquals([$null], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassSingleZeroParameterValueToConstructor(): void
    {
        $predicate  = new Expression('?', 0);
        $expression = new Argument(0, ArgumentType::Value);
        self::assertEquals([$expression], $predicate->getParameters());
    }

    #[Group('6849')]
    public function testCanPassSinglePredicateParameterToConstructor(): void
    {
        $predicate  = new IsNull('foo.baz');
        $expression = new Expression('?', $predicate);
        $isNull     = new Argument($predicate, ArgumentType::Select);
        self::assertEquals([$isNull], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassMultiScalarParametersToConstructor(): void
    {
        /** @psalm-suppress TooManyArguments */
        $expression = new Expression('? OR ?', 'foo', 'bar');
        $foo        = new Argument('foo', ArgumentType::Value);
        $bar        = new Argument('bar', ArgumentType::Value);

        self::assertEquals([$foo, $bar], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassMultiNullParametersToConstructor(): void
    {
        /** @psalm-suppress TooManyArguments */
        $expression = new Expression('? OR ?', null, null);
        $null       = new Argument(null, ArgumentType::Value);

        self::assertEquals([$null, $null], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCannotPassMultiPredicateParametersToConstructor(): void
    {
        $this->expectNotToPerformAssertions();
        /** @todo This test seems incorrect? */
        //$predicate = new IsNull('foo.baz');
        //$expression = new Expression('? OR ?', $predicate, $predicate);
        //$isNull     = new Argument($predicate, ArgumentType::Select);
        //self::assertEquals([$isNull], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassArrayOfOneScalarParameterToConstructor(): void
    {
        $expression = new Expression('?', ['foo']);
        $foo        = new Argument('foo', ArgumentType::Value);
        self::assertEquals([$foo], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassArrayOfMultiScalarsParameterToConstructor(): void
    {
        $expression = new Expression('? OR ?', ['foo', 'bar']);
        $foo        = new Argument('foo', ArgumentType::Value);
        $bar        = new Argument('bar', ArgumentType::Value);
        self::assertEquals([$foo, $bar], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassArrayOfOneNullParameterToConstructor(): void
    {
        $expression = new Expression('?', [null]);
        $null       = new Argument(null, ArgumentType::Value);
        self::assertEquals([$null], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassArrayOfMultiNullsParameterToConstructor(): void
    {
        $expression = new Expression('? OR ?', [null, null]);
        $null       = new Argument(null, ArgumentType::Value);
        self::assertEquals([$null, $null], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassArrayOfOnePredicateParameterToConstructor(): void
    {
        $predicate  = new IsNull('foo.baz');
        $expression = new Expression('?', [$predicate]);
        $isNull     = new Argument($predicate, ArgumentType::Select);
        self::assertEquals([$isNull], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassArrayOfMultiPredicatesParameterToConstructor(): void
    {
        $predicate  = new IsNull('foo.baz');
        $expression = new Expression('? OR ?', [$predicate, $predicate]);
        $isNull     = new Argument($predicate, ArgumentType::Select);
        self::assertEquals([$isNull, $isNull], $expression->getParameters());
    }

    public function testLiteralIsMutable(): void
    {
        $expression = new Expression();
        $expression->setExpression('foo.bar = ?');
        self::assertEquals('foo.bar = ?', $expression->getExpression());
    }

    public function testParameterIsMutable(): void
    {
        $expression = new Expression();
        $expression->setParameters(['foo', 'bar']);

        $parameter1 = new Argument('foo', ArgumentType::Value);
        $parameter2 = new Argument('bar', ArgumentType::Value);

        self::assertEquals([$parameter1, $parameter2], $expression->getParameters());
    }

    public function testRetrievingWherePartsReturnsSpecificationArrayOfLiteralAndParametersAndArrayOfTypes(): void
    {
        $expression = new Expression();
        $expression
            ->setExpression('foo.bar = ? AND id != ?')
            ->setParameters(['foo', 'bar']);

        $parameter1 = new Argument('foo', ArgumentType::Value);
        $parameter2 = new Argument('bar', ArgumentType::Value);

        $expressionData = $expression->getExpressionData();

        self::assertEquals('foo.bar = %s AND id != %s', $expressionData->getExpressionSpecification());
        self::assertEquals([$parameter1, $parameter2], $expressionData->getExpressionValues());
    }
}
