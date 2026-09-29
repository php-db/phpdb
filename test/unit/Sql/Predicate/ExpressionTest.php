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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ExpressionTest extends TestCase
{
    #[Test]
    #[Group('6849')]
    public function canPassArrayOfMultiNullsParameterToConstructor(): void
    {
        $expression = new Expression('? OR ?', [null, null]);
        $null       = new Value(null);
        static::assertEquals([$null, $null], $expression->getParameters());
    }

    #[Test]
    #[Group('6849')]
    public function canPassArrayOfMultiPredicatesParameterToConstructor(): void
    {
        $predicate  = new IsNull('foo.baz');
        $expression = new Expression('? OR ?', [$predicate, $predicate]);
        $isNull     = new Select($predicate);
        static::assertEquals([$isNull, $isNull], $expression->getParameters());
    }

    #[Test]
    #[Group('6849')]
    public function canPassArrayOfMultiScalarsParameterToConstructor(): void
    {
        $expression = new Expression('? OR ?', ['foo', 'bar']);
        $foo        = new Value('foo');
        $bar        = new Value('bar');
        static::assertEquals([$foo, $bar], $expression->getParameters());
    }

    #[Test]
    #[Group('6849')]
    public function canPassArrayOfOneNullParameterToConstructor(): void
    {
        $expression = new Expression('?', [null]);
        $null       = new Value(null);
        static::assertEquals([$null], $expression->getParameters());
    }

    #[Test]
    #[Group('6849')]
    public function canPassArrayOfOnePredicateParameterToConstructor(): void
    {
        $predicate  = new IsNull('foo.baz');
        $expression = new Expression('?', [$predicate]);
        $isNull     = new Select($predicate);
        static::assertEquals([$isNull], $expression->getParameters());
    }

    #[Test]
    #[Group('6849')]
    public function canPassArrayOfOneScalarParameterToConstructor(): void
    {
        $expression = new Expression('?', ['foo']);
        $foo        = new Value('foo');
        static::assertEquals([$foo], $expression->getParameters());
    }

    #[Test]
    #[Group('6849')]
    public function canPassLiteralAndSingleScalarParameterToConstructor(): void
    {
        $expression = new Expression('foo.bar = ?', 'bar');
        $bar        = new Value('bar');
        static::assertSame('foo.bar = ?', $expression->getExpression());
        static::assertEquals([$bar], $expression->getParameters());
    }

    #[Test]
    #[Group('6849')]
    public function canPassMultiNullParametersToConstructor(): void
    {
        /** @psalm-suppress TooManyArguments */
        $expression = new Expression('? OR ?', null, null);
        $null       = new Value(null);

        static::assertEquals([$null, $null], $expression->getParameters());
    }

    #[Test]
    #[Group('6849')]
    public function canPassMultiScalarParametersToConstructor(): void
    {
        /** @psalm-suppress TooManyArguments */
        $expression = new Expression('? OR ?', 'foo', 'bar');
        $foo        = new Value('foo');
        $bar        = new Value('bar');

        static::assertEquals([$foo, $bar], $expression->getParameters());
    }

    #[Test]
    #[Group('6849')]
    public function canPassNoParameterToConstructor(): void
    {
        $expression = new Expression('foo.bar');
        static::assertEquals([], $expression->getParameters());
    }

    #[Test]
    #[Group('6849')]
    public function canPassSingleNullParameterToConstructor(): void
    {
        $expression = new Expression('?', null);
        $null       = new Value(null);
        static::assertEquals([$null], $expression->getParameters());
    }

    #[Test]
    #[Group('6849')]
    public function canPassSinglePredicateParameterToConstructor(): void
    {
        $predicate  = new IsNull('foo.baz');
        $expression = new Expression('?', $predicate);
        $isNull     = new Select($predicate);
        static::assertEquals([$isNull], $expression->getParameters());
    }

    #[Test]
    #[Group('6849')]
    public function canPassSingleZeroParameterValueToConstructor(): void
    {
        $predicate  = new Expression('?', 0);
        $expression = new Value(0);
        static::assertEquals([$expression], $predicate->getParameters());
    }

    #[Test]
    public function emptyConstructorYieldsEmptyLiteralAndParameter(): void
    {
        $expression = new Expression();
        static::assertSame('', $expression->getExpression());
        static::assertEmpty($expression->getParameters());
    }

    #[Test]
    public function literalIsMutable(): void
    {
        $expression = new Expression();
        $expression->setExpression('foo.bar = ?');
        static::assertSame('foo.bar = ?', $expression->getExpression());
    }

    #[Test]
    public function parameterIsMutable(): void
    {
        $expression = new Expression();

        // First mutation
        $result = $expression->setParameters(['foo', 'bar']);

        // Verify fluent interface
        static::assertSame($expression, $result);

        // Verify the first mutation occurred - getParameters returns an array
        $parameters1 = $expression->getParameters();
        static::assertCount(2, $parameters1);
        static::assertInstanceOf(ArgumentInterface::class, $parameters1[0]);
        static::assertSame('foo', $parameters1[0]->getValue());
        static::assertEquals(ArgumentType::Value, $parameters1[0]->getType());
        static::assertInstanceOf(ArgumentInterface::class, $parameters1[1]);
        static::assertSame('bar', $parameters1[1]->getValue());
        static::assertEquals(ArgumentType::Value, $parameters1[1]->getType());

        // Second mutation with different data to verify mutability
        $expression->setParameters(['baz', 'qux', 'quux']);

        // Verify the instance was actually mutated - parameters are accumulated
        $parameters2 = $expression->getParameters();
        static::assertCount(5, $parameters2); // 2 original + 3 new = 5 total
        // First two are still there
        static::assertSame('foo', $parameters2[0]->getValue());
        static::assertSame('bar', $parameters2[1]->getValue());
        // New ones were appended
        static::assertInstanceOf(ArgumentInterface::class, $parameters2[2]);
        static::assertSame('baz', $parameters2[2]->getValue());
        static::assertEquals(ArgumentType::Value, $parameters2[2]->getType());
        static::assertInstanceOf(ArgumentInterface::class, $parameters2[3]);
        static::assertSame('qux', $parameters2[3]->getValue());
        static::assertEquals(ArgumentType::Value, $parameters2[3]->getType());
        static::assertInstanceOf(ArgumentInterface::class, $parameters2[4]);
        static::assertSame('quux', $parameters2[4]->getValue());
        static::assertEquals(ArgumentType::Value, $parameters2[4]->getType());
    }

    #[Test]
    public function retrievingWherePartsReturnsSpecificationArrayOfLiteralAndParametersAndArrayOfTypes(): void
    {
        $expression = new Expression();
        $expression->setExpression('foo.bar = ? AND id != ?')
            ->setParameters(['foo', 'bar']);

        $parameter1 = new Value('foo');
        $parameter2 = Argument::value('bar');

        $expressionData = $expression->getExpressionData();

        static::assertSame('foo.bar = %s AND id != %s', $expressionData['spec']);
        static::assertEquals([$parameter1, $parameter2], $expressionData['values']);
    }
}
