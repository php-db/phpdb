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

        self::assertCount(3, $expressionData->getExpressionParts());
        self::assertStringContainsString('AND', $expressionData->getExpressionSpecification());
        self::assertStringNotContainsString('OR', $expressionData->getExpressionSpecification());
    }

    public function testCanPassPredicatesAndDefaultCombinationViaConstructor(): void
    {
        new PredicateSet();
        $predicateSet = new PredicateSet([
            new IsNull('foo'),
            new IsNull('bar'),
        ], 'OR');

        $expressionData = $predicateSet->getExpressionData();

        self::assertCount(3, $expressionData->getExpressionParts());
        self::assertStringContainsString('OR', $expressionData->getExpressionSpecification());
        self::assertStringNotContainsString('AND', $expressionData->getExpressionSpecification());
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

        self::assertCount(7, $expressionData);

        self::assertStringNotContainsString('OR', $expressionData->getExpressionPart(1)->getSpecificationString());
        self::assertStringContainsString('AND', $expressionData->getExpressionPart(1)->getSpecificationString());

        self::assertStringContainsString('OR', $expressionData->getExpressionPart(3)->getSpecificationString());
        self::assertStringNotContainsString('AND', $expressionData->getExpressionPart(3)->getSpecificationString());

        self::assertStringNotContainsString('OR', $expressionData->getExpressionPart(5)->getSpecificationString());
        self::assertStringContainsString('AND', $expressionData->getExpressionPart(5)->getSpecificationString());
    }

    public function testCanUseOrPredicateAndAndPredicateMethods(): void
    {
        $predicateSet = new PredicateSet();
        $predicateSet->orPredicate(new IsNull('foo'))
                     ->andPredicate(new IsNull('bar'))
                     ->orPredicate(new IsNull('baz'))
                     ->andPredicate(new IsNull('bat'));

        $expressionData = $predicateSet->getExpressionData();

        self::assertCount(7, $expressionData);

        self::assertStringNotContainsString('OR', $expressionData->getExpressionPart(1)->getSpecificationString());
        self::assertStringContainsString('AND', $expressionData->getExpressionPart(1)->getSpecificationString());

        self::assertStringContainsString('OR', $expressionData->getExpressionPart(3)->getSpecificationString());
        self::assertStringNotContainsString('AND', $expressionData->getExpressionPart(3)->getSpecificationString());

        self::assertStringNotContainsString('OR', $expressionData->getExpressionPart(5)->getSpecificationString());
        self::assertStringContainsString('AND', $expressionData->getExpressionPart(5)->getSpecificationString());
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

        self::assertIsArray($predicates[0]);
        self::assertEquals('AND', $predicates[0][0]);
        self::assertInstanceOf(Literal::class, $predicates[0][1]);

        self::assertIsArray($predicates[1]);
        self::assertEquals('AND', $predicates[1][0]);
        self::assertInstanceOf(Expression::class, $predicates[1][1]);

        self::assertIsArray($predicates[2]);
        self::assertEquals('AND', $predicates[2][0]);
        self::assertInstanceOf(Operator::class, $predicates[2][1]);

        self::assertIsArray($predicates[3]);
        self::assertEquals('OR', $predicates[3][0]);
        self::assertInstanceOf(Literal::class, $predicates[3][1]);

        self::assertIsArray($predicates[4]);
        self::assertEquals('AND', $predicates[4][0]);
        self::assertInstanceOf(IsNull::class, $predicates[4][1]);

        self::assertIsArray($predicates[5]);
        self::assertEquals('AND', $predicates[5][0]);
        self::assertInstanceOf(In::class, $predicates[5][1]);

        self::assertIsArray($predicates[6]);
        self::assertEquals('AND', $predicates[6][0]);
        self::assertInstanceOf(IsNotNull::class, $predicates[6][1]);

        $predicateSet->addPredicates(function (PredicateSet $what) use ($predicateSet): void {
            self::assertSame($predicateSet, $what);
        });

        $this->expectException(TypeError::class);
        /** @noinspection PhpStrictTypeCheckingInspection */
        $predicateSet->addPredicates(null);
    }

    /**
     * Test that ExpressionInterface objects (not just PredicateInterface) can be added via addPredicates
     *
     * @throws ReflectionException
     */
    public function testAddPredicatesWithExpressionInterface(): void
    {
        $predicateSet = new PredicateSet();

        // Add a SqlExpression (ExpressionInterface) - not a Predicate\Expression (PredicateInterface)
        $predicateSet->addPredicates([
            new SqlExpression('COUNT(?) > ?', [Argument::identifier('id'), Argument::value(5)]),
        ]);

        $predicates = (array) $this->readAttribute($predicateSet, 'predicates');
        self::assertCount(1, $predicates);

        self::assertIsArray($predicates[0]);
        self::assertEquals('AND', $predicates[0][0]);
        // Should be wrapped in a Predicate\Expression
        self::assertInstanceOf(Expression::class, $predicates[0][1]);

        // Verify the expression data is preserved
        $expressionData = $predicateSet->getExpressionData();
        self::assertStringContainsString('COUNT', $expressionData->getExpressionSpecification());
    }

    /**
     * Test multiple ExpressionInterface objects with different combinations
     *
     * @throws ReflectionException
     */
    public function testAddPredicatesWithMultipleExpressionInterfaces(): void
    {
        $predicateSet = new PredicateSet();

        $predicateSet->addPredicates([
            new SqlExpression('SUM(?) > ?', [Argument::identifier('amount'), Argument::value(100)]),
            new SqlExpression('AVG(?) < ?', [Argument::identifier('price'), Argument::value(50)]),
        ]);

        $predicates = (array) $this->readAttribute($predicateSet, 'predicates');
        self::assertCount(2, $predicates);

        self::assertInstanceOf(Expression::class, $predicates[0][1]);
        self::assertInstanceOf(Expression::class, $predicates[1][1]);
    }
}
