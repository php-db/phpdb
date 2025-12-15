<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Expression as SqlExpression;
use PhpDb\Sql\Predicate\Expression;
use PhpDb\Sql\Predicate\In;
use PhpDb\Sql\Predicate\IsNotNull;
use PhpDb\Sql\Predicate\IsNull;
use PhpDb\Sql\Predicate\Literal;
use PhpDb\Sql\Predicate\Operator;
use PhpDb\Sql\Predicate\PredicateSet;
use PhpDbTest\DeprecatedAssertionsTrait;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use TypeError;

#[CoversMethod(PredicateSet::class, '__construct')]
#[CoversMethod(PredicateSet::class, 'addPredicate')]
#[CoversMethod(PredicateSet::class, 'addPredicates')]
#[CoversMethod(PredicateSet::class, 'getPredicates')]
#[CoversMethod(PredicateSet::class, 'orPredicate')]
#[CoversMethod(PredicateSet::class, 'andPredicate')]
#[CoversMethod(PredicateSet::class, 'getExpressionData')]
#[CoversMethod(PredicateSet::class, 'count')]
final class PredicateSetTest extends TestCase
{
    use DeprecatedAssertionsTrait;

    public function testEmptyConstructorYieldsCountOfZero(): void
    {
        $predicateSet = new PredicateSet();
        self::assertCount(0, $predicateSet);
    }

    public function testCombinationIsAndByDefault(): void
    {
        $predicateSet = new PredicateSet();
        $predicateSet
            ->addPredicate(new IsNull('foo'))
            ->addPredicate(new IsNull('bar'));

        $expressionData = $predicateSet->getExpressionData();

        // 2 predicates = 2 values
        self::assertCount(2, $expressionData['values']);
        self::assertStringContainsString('AND', $expressionData['spec']);
        self::assertStringNotContainsString('OR', $expressionData['spec']);
    }

    public function testCanPassPredicatesAndDefaultCombinationViaConstructor(): void
    {
        new PredicateSet();
        $predicateSet = new PredicateSet([
            new IsNull('foo'),
            new IsNull('bar'),
        ], 'OR');

        $expressionData = $predicateSet->getExpressionData();

        // 2 predicates = 2 values
        self::assertCount(2, $expressionData['values']);
        self::assertStringContainsString('OR', $expressionData['spec']);
        self::assertStringNotContainsString('AND', $expressionData['spec']);
    }

    public function testCanPassBothPredicateAndCombinationToAddPredicate(): void
    {
        $predicateSet = new PredicateSet();
        $predicateSet
            ->addPredicate(new IsNull('foo'), 'OR')
            ->addPredicate(new IsNull('bar'), 'AND')
            ->addPredicate(new IsNull('baz'), 'OR')
            ->addPredicate(new IsNull('bat'), 'AND');

        $expressionData = $predicateSet->getExpressionData();

        // 4 predicates = 4 values
        self::assertCount(4, $expressionData['values']);

        // Verify combinators are in spec string using %s placeholder format
        $spec = $expressionData['spec'];
        self::assertEquals('%s IS NULL AND %s IS NULL OR %s IS NULL AND %s IS NULL', $spec);
    }

    public function testCanUseOrPredicateAndAndPredicateMethods(): void
    {
        $predicateSet = new PredicateSet();
        $predicateSet->orPredicate(new IsNull('foo'))
                     ->andPredicate(new IsNull('bar'))
                     ->orPredicate(new IsNull('baz'))
                     ->andPredicate(new IsNull('bat'));

        $expressionData = $predicateSet->getExpressionData();

        // 4 predicates = 4 values
        self::assertCount(4, $expressionData['values']);

        // Verify spec contains correct pattern using %s placeholder format
        $spec = $expressionData['spec'];
        self::assertEquals('%s IS NULL AND %s IS NULL OR %s IS NULL AND %s IS NULL', $spec);
    }

    /**
     * @throws ReflectionException
     */
    public function testAddPredicates(): void
    {
        $predicateSet = new PredicateSet();

        $predicateSet->addPredicates('x = y');
        $predicateSet->addPredicates(['foo > ?' => 5]);
        $predicateSet->addPredicates(['id' => 2]);
        $predicateSet->addPredicates(['a = b'], PredicateSet::OP_OR);
        $predicateSet->addPredicates(['c1' => null]);
        $predicateSet->addPredicates(['c2' => [1, 2, 3]]);
        $predicateSet->addPredicates([new IsNotNull('c3')]);

        $predicates = (array) $this->readAttribute($predicateSet, 'predicates');
        self::assertCount(7, $predicates);

        // Predicates now store combination internally via getCombination()
        self::assertInstanceOf(Literal::class, $predicates[0]);
        self::assertEquals('AND', $predicates[0]->getCombination());

        self::assertInstanceOf(Expression::class, $predicates[1]);
        self::assertEquals('AND', $predicates[1]->getCombination());

        self::assertInstanceOf(Operator::class, $predicates[2]);
        self::assertEquals('AND', $predicates[2]->getCombination());

        self::assertInstanceOf(Literal::class, $predicates[3]);
        self::assertEquals('OR', $predicates[3]->getCombination());

        self::assertInstanceOf(IsNull::class, $predicates[4]);
        self::assertEquals('AND', $predicates[4]->getCombination());

        self::assertInstanceOf(In::class, $predicates[5]);
        self::assertEquals('AND', $predicates[5]->getCombination());

        self::assertInstanceOf(IsNotNull::class, $predicates[6]);
        self::assertEquals('AND', $predicates[6]->getCombination());

        $predicateSet->addPredicates(function (PredicateSet $what) use ($predicateSet): void {
            self::assertSame($predicateSet, $what);
        });

        $this->expectException(TypeError::class);
        /** @noinspection PhpStrictTypeCheckingInspection */
        $predicateSet->addPredicates(null);
    }

    /**
     * Test that Expression objects (not just PredicateInterface) can be added via addPredicates
     *
     * @throws ReflectionException
     */
    public function testAddPredicatesWithExpression(): void
    {
        $predicateSet = new PredicateSet();

        // Add a SqlExpression (Expression) - not a Predicate\Expression (PredicateInterface)
        $predicateSet->addPredicates([
            new SqlExpression('COUNT(?) > ?', [Argument::identifier('id'), Argument::value(5)]),
        ]);

        $predicates = (array) $this->readAttribute($predicateSet, 'predicates');
        self::assertCount(1, $predicates);

        // Predicates now store combination internally via getCombination()
        // Should be wrapped in a Predicate\Expression
        self::assertInstanceOf(Expression::class, $predicates[0]);
        self::assertEquals('AND', $predicates[0]->getCombination());

        // Verify the expression data is preserved
        $expressionData = $predicateSet->getExpressionData();
        self::assertStringContainsString('COUNT', $expressionData['spec']);
    }

    /**
     * Test multiple Expression objects with different combinations
     *
     * @throws ReflectionException
     */
    public function testAddPredicatesWithMultipleExpressions(): void
    {
        $predicateSet = new PredicateSet();

        $predicateSet->addPredicates([
            new SqlExpression('SUM(?) > ?', [Argument::identifier('amount'), Argument::value(100)]),
            new SqlExpression('AVG(?) < ?', [Argument::identifier('price'), Argument::value(50)]),
        ]);

        $predicates = (array) $this->readAttribute($predicateSet, 'predicates');
        self::assertCount(2, $predicates);

        self::assertInstanceOf(Expression::class, $predicates[0]);
        self::assertInstanceOf(Expression::class, $predicates[1]);
    }
}
