<?php

namespace LaminasTest\Db\Sql\Predicate;

use Laminas\Db\Sql\Exception\InvalidArgumentException;
use Laminas\Db\Sql\Predicate\Expression;
use Laminas\Db\Sql\Predicate\In;
use Laminas\Db\Sql\Predicate\IsNotNull;
use Laminas\Db\Sql\Predicate\IsNull;
use Laminas\Db\Sql\Predicate\Literal;
use Laminas\Db\Sql\Predicate\Operator;
use Laminas\Db\Sql\Predicate\PredicateSet;
use LaminasTest\Db\DeprecatedAssertionsTrait;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use ReflectionException;

use function var_export;

#[CoversMethod(PredicateSet::class, 'addPredicates')]
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
        $predicateSet->addPredicate(new IsNull('foo'))
                  ->addPredicate(new IsNull('bar'));
        $parts = $predicateSet->getExpressionData();
        self::assertCount(3, $parts);

        self::assertStringContainsString('AND', (string) $parts[1]);
        self::assertStringNotContainsString('OR', (string) $parts[1]);
    }

    public function testCanPassPredicatesAndDefaultCombinationViaConstructor(): void
    {
        new PredicateSet();
        $set   = new PredicateSet([
            new IsNull('foo'),
            new IsNull('bar'),
        ], 'OR');
        $parts = $set->getExpressionData();
        self::assertCount(3, $parts);
        self::assertStringContainsString('OR', (string) $parts[1]);
        self::assertStringNotContainsString('AND', (string) $parts[1]);
    }

    public function testCanPassBothPredicateAndCombinationToAddPredicate(): void
    {
        $predicateSet = new PredicateSet();
        $predicateSet->addPredicate(new IsNull('foo'), 'OR')
                  ->addPredicate(new IsNull('bar'), 'AND')
                  ->addPredicate(new IsNull('baz'), 'OR')
                  ->addPredicate(new IsNull('bat'), 'AND');
        $parts = $predicateSet->getExpressionData();
        self::assertCount(7, $parts);

        self::assertStringNotContainsString('OR', (string) $parts[1], var_export($parts, true));
        self::assertStringContainsString('AND', (string) $parts[1]);

        self::assertStringContainsString('OR', (string) $parts[3]);
        self::assertStringNotContainsString('AND', (string) $parts[3]);

        self::assertStringNotContainsString('OR', (string) $parts[5]);
        self::assertStringContainsString('AND', (string) $parts[5]);
    }

    public function testCanUseOrPredicateAndAndPredicateMethods(): void
    {
        $predicateSet = new PredicateSet();
        $predicateSet->orPredicate(new IsNull('foo'))
                  ->andPredicate(new IsNull('bar'))
                  ->orPredicate(new IsNull('baz'))
                  ->andPredicate(new IsNull('bat'));
        $parts = $predicateSet->getExpressionData();
        self::assertCount(7, $parts);

        self::assertStringNotContainsString('OR', (string) $parts[1], var_export($parts, true));
        self::assertStringContainsString('AND', (string) $parts[1]);

        self::assertStringContainsString('OR', (string) $parts[3]);
        self::assertStringNotContainsString('AND', (string) $parts[3]);

        self::assertStringNotContainsString('OR', (string) $parts[5]);
        self::assertStringContainsString('AND', (string) $parts[5]);
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

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Predicate cannot be null');
        /** @psalm-suppress NullArgument - ensure an exception is thrown */
        $predicateSet->addPredicates(null);
    }
}
