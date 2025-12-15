<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Argument\Select;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\ArgumentInterface;
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
        $bar        = new Value('bar');
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
        // Pass null in an array to be processed as a parameter
        $expression = new Expression('?', [null]);
        $null       = new Value(null);
        self::assertEquals([$null], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassSingleZeroParameterValueToConstructor(): void
    {
        $predicate  = new Expression('?', 0);
        $expression = new Value(0);
        self::assertEquals([$expression], $predicate->getParameters());
    }

    #[Group('6849')]
    public function testCanPassSinglePredicateParameterToConstructor(): void
    {
        $predicate  = new IsNull('foo.baz');
        $expression = new Expression('?', $predicate);
        $isNull     = new Select($predicate);
        self::assertEquals([$isNull], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassMultiScalarParametersToConstructor(): void
    {
        // Multiple parameters must be passed as an array
        $expression = new Expression('? OR ?', ['foo', 'bar']);
        $foo        = new Value('foo');
        $bar        = new Value('bar');

        self::assertEquals([$foo, $bar], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassMultiNullParametersToConstructor(): void
    {
        // Multiple null parameters must be passed as an array
        $expression = new Expression('? OR ?', [null, null]);
        $null       = new Value(null);

        self::assertEquals([$null, $null], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassArrayOfOneScalarParameterToConstructor(): void
    {
        $expression = new Expression('?', ['foo']);
        $foo        = new Value('foo');
        self::assertEquals([$foo], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassArrayOfMultiScalarsParameterToConstructor(): void
    {
        $expression = new Expression('? OR ?', ['foo', 'bar']);
        $foo        = new Value('foo');
        $bar        = new Value('bar');
        self::assertEquals([$foo, $bar], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassArrayOfOneNullParameterToConstructor(): void
    {
        $expression = new Expression('?', [null]);
        $null       = new Value(null);
        self::assertEquals([$null], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassArrayOfMultiNullsParameterToConstructor(): void
    {
        $expression = new Expression('? OR ?', [null, null]);
        $null       = new Value(null);
        self::assertEquals([$null, $null], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassArrayOfOnePredicateParameterToConstructor(): void
    {
        $predicate  = new IsNull('foo.baz');
        $expression = new Expression('?', [$predicate]);
        $isNull     = new Select($predicate);
        self::assertEquals([$isNull], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassArrayOfMultiPredicatesParameterToConstructor(): void
    {
        $predicate  = new IsNull('foo.baz');
        $expression = new Expression('? OR ?', [$predicate, $predicate]);
        $isNull     = new Select($predicate);
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

        // First mutation
        $result = $expression->setParameters(['foo', 'bar']);

        // Verify fluent interface
        self::assertSame($expression, $result);

        // Verify the first mutation occurred - getParameters returns an array
        $parameters1 = $expression->getParameters();
        self::assertCount(2, $parameters1);
        self::assertInstanceOf(ArgumentInterface::class, $parameters1[0]);
        self::assertEquals('foo', $parameters1[0]->getValue());
        self::assertEquals(ArgumentType::Value, $parameters1[0]->getType());
        self::assertInstanceOf(ArgumentInterface::class, $parameters1[1]);
        self::assertEquals('bar', $parameters1[1]->getValue());
        self::assertEquals(ArgumentType::Value, $parameters1[1]->getType());

        // Second mutation with different data to verify mutability
        $expression->setParameters(['baz', 'qux', 'quux']);

        // Verify the instance was actually mutated - parameters are accumulated
        $parameters2 = $expression->getParameters();
        self::assertCount(5, $parameters2); // 2 original + 3 new = 5 total
        // First two are still there
        self::assertEquals('foo', $parameters2[0]->getValue());
        self::assertEquals('bar', $parameters2[1]->getValue());
        // New ones were appended
        self::assertInstanceOf(ArgumentInterface::class, $parameters2[2]);
        self::assertEquals('baz', $parameters2[2]->getValue());
        self::assertEquals(ArgumentType::Value, $parameters2[2]->getType());
        self::assertInstanceOf(ArgumentInterface::class, $parameters2[3]);
        self::assertEquals('qux', $parameters2[3]->getValue());
        self::assertEquals(ArgumentType::Value, $parameters2[3]->getType());
        self::assertInstanceOf(ArgumentInterface::class, $parameters2[4]);
        self::assertEquals('quux', $parameters2[4]->getValue());
        self::assertEquals(ArgumentType::Value, $parameters2[4]->getType());
    }

    public function testRetrievingWherePartsReturnsSpecificationArrayOfLiteralAndParametersAndArrayOfTypes(): void
    {
        $expression = new Expression();
        $expression
            ->setExpression('foo.bar = ? AND id != ?')
            ->setParameters(['foo', 'bar']);

        $parameter1 = new Value('foo');
        $parameter2 = Argument::value('bar');

        $expressionData = $expression->getExpressionData();

        self::assertEquals('foo.bar = %s AND id != %s', $expressionData['spec']);
        self::assertEquals([$parameter1, $parameter2], $expressionData['values']);
    }
}
