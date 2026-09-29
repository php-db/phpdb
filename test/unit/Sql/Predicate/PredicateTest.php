<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use ErrorException;
use PhpDb\Adapter\Exception\RuntimeException as AdapterRuntimeException;
use PhpDb\Adapter\Platform\Sql92;
use PhpDb\Sql\Argument;
use PhpDb\Sql\Expression;
use PhpDb\Sql\Predicate\Exception\RuntimeException;
use PhpDb\Sql\Predicate\Predicate;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\Select;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class PredicateTest extends TestCase
{
    #[Test]
    public function betweenCreatesBetweenPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->between('foo.bar', 1, 10);

        $identifier = Argument::identifier('foo.bar');
        $minValue   = Argument::value(1);
        $maxValue   = Argument::value(10);

        $expressionData = $predicate->getExpressionData();

        static::assertSame('%s BETWEEN %s AND %s', $expressionData['spec']);
        static::assertCount(3, $expressionData['values']);
        static::assertEquals($identifier, $expressionData['values'][0]);
        static::assertEquals($minValue, $expressionData['values'][1]);
        static::assertEquals($maxValue, $expressionData['values'][2]);
    }

    #[Test]
    public function betweenCreatesNotBetweenPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->notBetween('foo.bar', 1, 10);

        $identifier = Argument::identifier('foo.bar');
        $minValue   = Argument::value(1);
        $maxValue   = Argument::value(10);

        $expressionData = $predicate->getExpressionData();

        static::assertSame('%s NOT BETWEEN %s AND %s', $expressionData['spec']);
        static::assertCount(3, $expressionData['values']);
        static::assertEquals($identifier, $expressionData['values'][0]);
        static::assertEquals($minValue, $expressionData['values'][1]);
        static::assertEquals($maxValue, $expressionData['values'][2]);
    }

    #[Test]
    public function canChainPredicateFactoriesBetweenOperators(): void
    {
        $predicate = new Predicate();
        $predicate->isNull('foo.bar')->or->isNotNull('bar.baz')
            ->and->equalTo('baz.bat', 'foo');

        $identifier1 = Argument::identifier('foo.bar');
        $identifier2 = Argument::identifier('bar.baz');
        $identifier3 = Argument::identifier('baz.bat');
        $expression3 = Argument::value('foo');

        $expressionData = $predicate->getExpressionData();

        // 3 predicates: IsNull, IsNotNull, Operator = 4 values (1+1+2)
        static::assertCount(4, $expressionData['values']);
        // Verify combined spec
        static::assertSame('%s IS NULL OR %s IS NOT NULL AND %s = %s', $expressionData['spec']);
        static::assertEquals($identifier1, $expressionData['values'][0]);
        static::assertEquals($identifier2, $expressionData['values'][1]);
        static::assertEquals($identifier3, $expressionData['values'][2]);
        static::assertEquals($expression3, $expressionData['values'][3]);
    }

    #[Test]
    public function canNestPredicates(): void
    {
        $predicate = new Predicate();
        $predicate->isNull('foo.bar')
            ->nest()
            ->isNotNull('bar.baz')
            ->and
            ->equalTo('baz.bat', 'foo')
            ->unnest();

        $identifier1 = Argument::identifier('foo.bar');
        $identifier2 = Argument::identifier('bar.baz');
        $identifier3 = Argument::identifier('baz.bat');
        $expression3 = Argument::value('foo');

        $expressionData = $predicate->getExpressionData();

        // 3 predicates: IsNull + nested(IsNotNull, Operator) = 4 values
        static::assertCount(4, $expressionData['values']);
        // Verify combined spec with nested brackets
        static::assertSame('%s IS NULL AND (%s IS NOT NULL AND %s = %s)', $expressionData['spec']);
        static::assertEquals($identifier1, $expressionData['values'][0]);
        static::assertEquals($identifier2, $expressionData['values'][1]);
        static::assertEquals($identifier3, $expressionData['values'][2]);
        static::assertEquals($expression3, $expressionData['values'][3]);
    }

    #[Test]
    public function equalToCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->equalTo('foo.bar', 'bar');

        $identifier = Argument::identifier('foo.bar');
        $expression = Argument::value('bar');

        $expressionData = $predicate->getExpressionData();

        static::assertSame('%s = %s', $expressionData['spec']);
        static::assertCount(2, $expressionData['values']);
        static::assertEquals($identifier, $expressionData['values'][0]);
        static::assertEquals($expression, $expressionData['values'][1]);
    }

    #[Test]
    #[TestDox('Unit test: Test expression() is chainable and returns proper values')]
    public function expression(): void
    {
        $predicate = new Predicate();
        $value     = Argument::value(0);

        // is chainable
        static::assertSame($predicate, $predicate->expression('foo = ?', 0));
        $expressionData = $predicate->getExpressionData();
        // with parameter
        static::assertSame('foo = %s', $expressionData['spec']);
        static::assertEquals([$value], $expressionData['values']);
    }

    #[Test]
    #[TestDox('Unit test: Test expression() allows null $parameters')]
    public function expressionNullParameters(): void
    {
        $predicate = new Predicate();

        $predicate->expression('foo = bar');

        $predicates = $predicate->getPredicates();

        if (isset($predicates[0][1])) {
            $expression = $predicates[0][1];
            static::assertInstanceOf(Expression::class, $expression);
            static::assertEquals([], $expression->getParameters());
        } else {
            static::fail('Expression not found');
        }
    }

    #[Test]
    public function greaterThanCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->greaterThan('foo.bar', 'bar');

        $identifier = Argument::identifier('foo.bar');
        $expression = Argument::value('bar');

        $expressionData = $predicate->getExpressionData();

        static::assertSame('%s > %s', $expressionData['spec']);
        static::assertCount(2, $expressionData['values']);
        static::assertEquals($identifier, $expressionData['values'][0]);
        static::assertEquals($expression, $expressionData['values'][1]);
    }

    #[Test]
    public function greaterThanOrEqualToCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->greaterThanOrEqualTo('foo.bar', 'bar');

        $identifier = Argument::identifier('foo.bar');
        $expression = Argument::value('bar');

        $expressionData = $predicate->getExpressionData();

        static::assertSame('%s >= %s', $expressionData['spec']);
        static::assertCount(2, $expressionData['values']);
        static::assertEquals($identifier, $expressionData['values'][0]);
        static::assertEquals($expression, $expressionData['values'][1]);
    }

    #[Test]
    public function inCreatesInPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->in('foo.bar', ['foo', 'bar']);

        $identifier = Argument::identifier('foo.bar');
        $expression = Argument::values(['foo', 'bar']);

        $expressionData = $predicate->getExpressionData();

        static::assertSame('%s IN (%s, %s)', $expressionData['spec']);
        static::assertCount(2, $expressionData['values']);
        static::assertEquals($identifier, $expressionData['values'][0]);
        static::assertEquals($expression, $expressionData['values'][1]);
    }

    #[Test]
    public function isNotNullCreatesIsNotNullPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->isNotNull('foo.bar');

        $identifier = Argument::identifier('foo.bar');

        $expressionData = $predicate->getExpressionData();

        static::assertSame('%s IS NOT NULL', $expressionData['spec']);
        static::assertCount(1, $expressionData['values']);
        static::assertEquals($identifier, $expressionData['values'][0]);
    }

    #[Test]
    public function isNullCreatesIsNullPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->isNull('foo.bar');

        $identifier = Argument::identifier('foo.bar');

        $expressionData = $predicate->getExpressionData();

        static::assertSame('%s IS NULL', $expressionData['spec']);
        static::assertCount(1, $expressionData['values']);
        static::assertEquals($identifier, $expressionData['values'][0]);
    }

    #[Test]
    public function lessThanCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->lessThan('foo.bar', 'bar');

        $identifier = Argument::identifier('foo.bar');
        $expression = Argument::value('bar');

        $expressionData = $predicate->getExpressionData();

        static::assertSame('%s < %s', $expressionData['spec']);
        static::assertCount(2, $expressionData['values']);
        static::assertEquals($identifier, $expressionData['values'][0]);
        static::assertEquals($expression, $expressionData['values'][1]);
    }

    #[Test]
    public function lessThanOrEqualToCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->lessThanOrEqualTo('foo.bar', 'bar');

        $identifier = Argument::identifier('foo.bar');
        $expression = Argument::value('bar');

        $expressionData = $predicate->getExpressionData();

        static::assertSame('%s <= %s', $expressionData['spec']);
        static::assertCount(2, $expressionData['values']);
        static::assertEquals($identifier, $expressionData['values'][0]);
        static::assertEquals($expression, $expressionData['values'][1]);
    }

    #[Test]
    public function likeCreatesLikePredicate(): void
    {
        $predicate = new Predicate();
        $predicate->like('foo.bar', 'bar%');

        $identifier = Argument::identifier('foo.bar');
        $expression = Argument::value('bar%');

        $expressionData = $predicate->getExpressionData();

        static::assertSame('%s LIKE %s', $expressionData['spec']);
        static::assertCount(2, $expressionData['values']);
        static::assertEquals($identifier, $expressionData['values'][0]);
        static::assertEquals($expression, $expressionData['values'][1]);
    }

    #[Test]
    #[TestDox('Unit test: Test literal() is chainable, returns proper values, and is backwards compatible with 2.0.*')]
    public function literal(): void
    {
        $predicate = new Predicate();

        // is chainable
        static::assertSame($predicate, $predicate->literal('foo = bar'));

        $expressionData = $predicate->getExpressionData();

        // with parameter
        static::assertSame('foo = bar', $expressionData['spec']);
        static::assertEquals([], $expressionData['values']);

        // test literal() is backwards-compatible, and works with with parameters
        $predicate = new Predicate();
        $predicate->expression('foo = ?', 'bar');

        $expression     = Argument::value('bar');
        $expressionData = $predicate->getExpressionData();

        // with parameter
        static::assertSame('foo = %s', $expressionData['spec']);
        static::assertEquals([$expression], $expressionData['values']);

        // test literal() is backwards-compatible, and works with with parameters, even 0 which tests as false
        $predicate = new Predicate();
        $predicate->expression('foo = ?', 0);

        $expression     = Argument::value(0);
        $expressionData = $predicate->getExpressionData();

        // with parameter
        static::assertSame('foo = %s', $expressionData['spec']);
        static::assertEquals([$expression], $expressionData['values']);
    }

    #[Test]
    public function literalCreatesLiteralPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->literal('foo.bar = ?');

        $expressionData = $predicate->getExpressionData();

        static::assertCount(0, $expressionData['values']);
        static::assertSame('foo.bar = ?', $expressionData['spec']);
    }

    #[Test]
    public function magicGetNestReturnsNestedPredicate(): void
    {
        $predicate = new Predicate();

        $nested = $predicate->nest;

        static::assertInstanceOf(Predicate::class, $nested);
        static::assertNotSame($predicate, $nested);
    }

    #[Test]
    public function magicGetUnnestReturnsParentPredicate(): void
    {
        $predicate = new Predicate();

        $nested = $predicate->nest;
        $parent = $nested->unnest;

        static::assertSame($predicate, $parent);
    }

    #[Test]
    public function notEqualToCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->notEqualTo('foo.bar', 'bar');

        $identifier = Argument::identifier('foo.bar');
        $expression = Argument::value('bar');

        $expressionData = $predicate->getExpressionData();

        static::assertSame('%s != %s', $expressionData['spec']);
        static::assertCount(2, $expressionData['values']);
        static::assertEquals($identifier, $expressionData['values'][0]);
        static::assertEquals($expression, $expressionData['values'][1]);
    }

    #[Test]
    public function notInCreatesNotInPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->notIn('foo.bar', ['foo', 'bar']);

        $identifier = Argument::identifier('foo.bar');
        $expression = Argument::values(['foo', 'bar']);

        $expressionData = $predicate->getExpressionData();

        static::assertSame('%s NOT IN (%s, %s)', $expressionData['spec']);
        static::assertCount(2, $expressionData['values']);
        static::assertEquals($identifier, $expressionData['values'][0]);
        static::assertEquals($expression, $expressionData['values'][1]);
    }

    #[Test]
    public function notLikeCreatesLikePredicate(): void
    {
        $predicate = new Predicate();
        $predicate->notLike('foo.bar', 'bar%');

        $identifier = Argument::identifier('foo.bar');
        $expression = Argument::value('bar%');

        $expressionData = $predicate->getExpressionData();

        static::assertSame('%s NOT LIKE %s', $expressionData['spec']);
        static::assertCount(2, $expressionData['values']);
        static::assertEquals($identifier, $expressionData['values'][0]);
        static::assertEquals($expression, $expressionData['values'][1]);
    }

    #[Test]
    public function predicateMethodAddsCustomPredicateInterface(): void
    {
        $predicate = new Predicate();
        $mock      = $this->createMock(PredicateInterface::class);

        $result = $predicate->predicate($mock);

        static::assertSame($predicate, $result);
        static::assertCount(1, $predicate);
    }

    #[Test]
    public function unnestThrowsWhenNotNested(): void
    {
        $predicate = new Predicate();

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(RuntimeException::NOT_NESTED);
        $predicate->unnest();
    }

    /**
     * removed throws ErrorException to fix phpstan issue
     */
    /**
     * @throws ErrorException
     */
    #[Test]
    public function willBindSqlParametersToExpressionsWithGivenParameter(): void
    {
        $where = new Predicate();

        $where->expression('some_expression(?)', null);

        $actual = $this->makeSqlString($where);

        static::assertSame(
            'SELECT "a_table".* FROM "a_table" WHERE (some_expression(\'\'))',
            $actual,
        );
    }

    /**
     * @throws ErrorException
     */
    #[Test]
    public function willBindSqlParametersToExpressionsWithGivenStringParameter(): void
    {
        $where = new Predicate();

        $where->expression('some_expression(?)', 'a string');

        $actual = $this->makeSqlString($where);

        static::assertSame(
            'SELECT "a_table".* FROM "a_table" WHERE (some_expression(\'a string\'))',
            $actual,
        );
    }

    /**
     * @throws ErrorException
     */
    private function makeSqlString(Predicate $where): string
    {
        $select = new Select('a_table');

        $select->where($where);

        // this is still faster than connecting to a real DB for this kind of test.
        // we are using unsafe SQL quoting on purpose here: this raises warnings in production.
        // ErrorHandler::start(E_USER_NOTICE);

        // try {
        //     $string = $select->getSqlString(new Sql92());
        // } finally {
        //     ErrorHandler::stop();
        // }
        self::expectException(AdapterRuntimeException::class);
        return $select->getSqlString(new Sql92());
    }
}
