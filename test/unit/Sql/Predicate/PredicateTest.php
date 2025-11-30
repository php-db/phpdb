<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate;

use ErrorException;
use Laminas\Stdlib\ErrorHandler;
use PhpDb\Adapter\Platform\Sql92;
use PhpDb\Sql\Argument;
use PhpDb\Sql\Expression;
use PhpDb\Sql\Predicate\Predicate;
use PhpDb\Sql\Select;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

use const E_USER_NOTICE;

final class PredicateTest extends TestCase
{
    public function testEqualToCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->equalTo('foo.bar', 'bar');

        $identifier = Argument::identifier('foo.bar');
        $expression = Argument::value('bar');

        $expressionData = $predicate->getExpressionData();

        self::assertEquals('%s = %s', $expressionData['spec']);
        self::assertCount(2, $expressionData['values']);
        self::assertEquals($identifier, $expressionData['values'][0]);
        self::assertEquals($expression, $expressionData['values'][1]);
    }

    public function testNotEqualToCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->notEqualTo('foo.bar', 'bar');

        $identifier = Argument::identifier('foo.bar');
        $expression = Argument::value('bar');

        $expressionData = $predicate->getExpressionData();

        self::assertEquals('%s != %s', $expressionData['spec']);
        self::assertCount(2, $expressionData['values']);
        self::assertEquals($identifier, $expressionData['values'][0]);
        self::assertEquals($expression, $expressionData['values'][1]);
    }

    public function testLessThanCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->lessThan('foo.bar', 'bar');

        $identifier = Argument::identifier('foo.bar');
        $expression = Argument::value('bar');

        $expressionData = $predicate->getExpressionData();

        self::assertEquals('%s < %s', $expressionData['spec']);
        self::assertCount(2, $expressionData['values']);
        self::assertEquals($identifier, $expressionData['values'][0]);
        self::assertEquals($expression, $expressionData['values'][1]);
    }

    public function testGreaterThanCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->greaterThan('foo.bar', 'bar');

        $identifier = Argument::identifier('foo.bar');
        $expression = Argument::value('bar');

        $expressionData = $predicate->getExpressionData();

        self::assertEquals('%s > %s', $expressionData['spec']);
        self::assertCount(2, $expressionData['values']);
        self::assertEquals($identifier, $expressionData['values'][0]);
        self::assertEquals($expression, $expressionData['values'][1]);
    }

    public function testLessThanOrEqualToCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->lessThanOrEqualTo('foo.bar', 'bar');

        $identifier = Argument::identifier('foo.bar');
        $expression = Argument::value('bar');

        $expressionData = $predicate->getExpressionData();

        self::assertEquals('%s <= %s', $expressionData['spec']);
        self::assertCount(2, $expressionData['values']);
        self::assertEquals($identifier, $expressionData['values'][0]);
        self::assertEquals($expression, $expressionData['values'][1]);
    }

    public function testGreaterThanOrEqualToCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->greaterThanOrEqualTo('foo.bar', 'bar');

        $identifier = Argument::identifier('foo.bar');
        $expression = Argument::value('bar');

        $expressionData = $predicate->getExpressionData();

        self::assertEquals('%s >= %s', $expressionData['spec']);
        self::assertCount(2, $expressionData['values']);
        self::assertEquals($identifier, $expressionData['values'][0]);
        self::assertEquals($expression, $expressionData['values'][1]);
    }

    public function testLikeCreatesLikePredicate(): void
    {
        $predicate = new Predicate();
        $predicate->like('foo.bar', 'bar%');

        $identifier = Argument::identifier('foo.bar');
        $expression = Argument::value('bar%');

        $expressionData = $predicate->getExpressionData();

        self::assertEquals('%s LIKE %s', $expressionData['spec']);
        self::assertCount(2, $expressionData['values']);
        self::assertEquals($identifier, $expressionData['values'][0]);
        self::assertEquals($expression, $expressionData['values'][1]);
    }

    public function testNotLikeCreatesLikePredicate(): void
    {
        $predicate = new Predicate();
        $predicate->notLike('foo.bar', 'bar%');

        $identifier = Argument::identifier('foo.bar');
        $expression = Argument::value('bar%');

        $expressionData = $predicate->getExpressionData();

        self::assertEquals('%s NOT LIKE %s', $expressionData['spec']);
        self::assertCount(2, $expressionData['values']);
        self::assertEquals($identifier, $expressionData['values'][0]);
        self::assertEquals($expression, $expressionData['values'][1]);
    }

    public function testLiteralCreatesLiteralPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->literal('foo.bar = ?');

        $expressionData = $predicate->getExpressionData();

        self::assertCount(0, $expressionData['values']);
        self::assertEquals('foo.bar = ?', $expressionData['spec']);
    }

    public function testIsNullCreatesIsNullPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->isNull('foo.bar');

        $identifier = Argument::identifier('foo.bar');

        $expressionData = $predicate->getExpressionData();

        self::assertEquals('%s IS NULL', $expressionData['spec']);
        self::assertCount(1, $expressionData['values']);
        self::assertEquals($identifier, $expressionData['values'][0]);
    }

    public function testIsNotNullCreatesIsNotNullPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->isNotNull('foo.bar');

        $identifier = Argument::identifier('foo.bar');

        $expressionData = $predicate->getExpressionData();

        self::assertEquals('%s IS NOT NULL', $expressionData['spec']);
        self::assertCount(1, $expressionData['values']);
        self::assertEquals($identifier, $expressionData['values'][0]);
    }

    public function testInCreatesInPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->in('foo.bar', ['foo', 'bar']);

        $identifier = Argument::identifier('foo.bar');
        $expression = Argument::values(['foo', 'bar']);

        $expressionData = $predicate->getExpressionData();

        self::assertEquals('%s IN (%s, %s)', $expressionData['spec']);
        self::assertCount(2, $expressionData['values']);
        self::assertEquals($identifier, $expressionData['values'][0]);
        self::assertEquals($expression, $expressionData['values'][1]);
    }

    public function testNotInCreatesNotInPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->notIn('foo.bar', ['foo', 'bar']);

        $identifier = Argument::identifier('foo.bar');
        $expression = Argument::values(['foo', 'bar']);

        $expressionData = $predicate->getExpressionData();

        self::assertEquals('%s NOT IN (%s, %s)', $expressionData['spec']);
        self::assertCount(2, $expressionData['values']);
        self::assertEquals($identifier, $expressionData['values'][0]);
        self::assertEquals($expression, $expressionData['values'][1]);
    }

    public function testBetweenCreatesBetweenPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->between('foo.bar', 1, 10);

        $identifier = Argument::identifier('foo.bar');
        $minValue   = Argument::value(1);
        $maxValue   = Argument::value(10);

        $expressionData = $predicate->getExpressionData();

        self::assertEquals('%s BETWEEN %s AND %s', $expressionData['spec']);
        self::assertCount(3, $expressionData['values']);
        self::assertEquals($identifier, $expressionData['values'][0]);
        self::assertEquals($minValue, $expressionData['values'][1]);
        self::assertEquals($maxValue, $expressionData['values'][2]);
    }

    public function testBetweenCreatesNotBetweenPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->notBetween('foo.bar', 1, 10);

        $identifier = Argument::identifier('foo.bar');
        $minValue   = Argument::value(1);
        $maxValue   = Argument::value(10);

        $expressionData = $predicate->getExpressionData();

        self::assertEquals('%s NOT BETWEEN %s AND %s', $expressionData['spec']);
        self::assertCount(3, $expressionData['values']);
        self::assertEquals($identifier, $expressionData['values'][0]);
        self::assertEquals($minValue, $expressionData['values'][1]);
        self::assertEquals($maxValue, $expressionData['values'][2]);
    }

    public function testCanChainPredicateFactoriesBetweenOperators(): void
    {
        $predicate = new Predicate();
        $predicate->isNull('foo.bar')
            ->or
            ->isNotNull('bar.baz')
            ->and
            ->equalTo('baz.bat', 'foo');

        $identifier1 = Argument::identifier('foo.bar');
        $identifier2 = Argument::identifier('bar.baz');
        $identifier3 = Argument::identifier('baz.bat');
        $expression3 = Argument::value('foo');

        $expressionData = $predicate->getExpressionData();

        // 3 predicates: IsNull, IsNotNull, Operator = 4 values (1+1+2)
        self::assertCount(4, $expressionData['values']);
        // Verify combined spec
        self::assertEquals('%s IS NULL OR %s IS NOT NULL AND %s = %s', $expressionData['spec']);
        self::assertEquals($identifier1, $expressionData['values'][0]);
        self::assertEquals($identifier2, $expressionData['values'][1]);
        self::assertEquals($identifier3, $expressionData['values'][2]);
        self::assertEquals($expression3, $expressionData['values'][3]);
    }

    public function testCanNestPredicates(): void
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
        self::assertCount(4, $expressionData['values']);
        // Verify combined spec with nested brackets
        self::assertEquals('%s IS NULL AND (%s IS NOT NULL AND %s = %s)', $expressionData['spec']);
        self::assertEquals($identifier1, $expressionData['values'][0]);
        self::assertEquals($identifier2, $expressionData['values'][1]);
        self::assertEquals($identifier3, $expressionData['values'][2]);
        self::assertEquals($expression3, $expressionData['values'][3]);
    }

    #[TestDox('Unit test: Test expression() is chainable and returns proper values')]
    public function testExpression(): void
    {
        $predicate = new Predicate();
        $value     = Argument::value(0);

        // is chainable
        self::assertSame($predicate, $predicate->expression('foo = ?', 0));
        $expressionData = $predicate->getExpressionData();
        // with parameter
        self::assertEquals('foo = %s', $expressionData['spec']);
        self::assertEquals([$value], $expressionData['values']);
    }

    #[TestDox('Unit test: Test expression() allows null $parameters')]
    public function testExpressionNullParameters(): void
    {
        $predicate = new Predicate();

        $predicate->expression('foo = bar');

        $predicates = $predicate->getPredicates();

        if (isset($predicates[0][1])) {
            $expression = $predicates[0][1];
            $this->assertInstanceOf(Expression::class, $expression);
            self::assertEquals([], $expression->getParameters());
        } else {
            $this->fail('Expression not found');
        }
    }

    #[TestDox('Unit test: Test literal() is chainable, returns proper values, and is backwards compatible with 2.0.*')]
    public function testLiteral(): void
    {
        $predicate = new Predicate();

        // is chainable
        self::assertSame($predicate, $predicate->literal('foo = bar'));

        $expressionData = $predicate->getExpressionData();

        // with parameter
        self::assertEquals('foo = bar', $expressionData['spec']);
        self::assertEquals([], $expressionData['values']);

        // test literal() is backwards-compatible, and works with with parameters
        $predicate = new Predicate();
        $predicate->expression('foo = ?', 'bar');

        $expression     = Argument::value('bar');
        $expressionData = $predicate->getExpressionData();

        // with parameter
        self::assertEquals('foo = %s', $expressionData['spec']);
        self::assertEquals([$expression], $expressionData['values']);

        // test literal() is backwards-compatible, and works with with parameters, even 0 which tests as false
        $predicate = new Predicate();
        $predicate->expression('foo = ?', 0);

        $expression     = Argument::value(0);
        $expressionData = $predicate->getExpressionData();

        // with parameter
        self::assertEquals('foo = %s', $expressionData['spec']);
        self::assertEquals([$expression], $expressionData['values']);
    }

    /**
     * @throws ErrorException
     */
    public function testCanCreateExpressionsWithoutAnyBoundSqlParameters(): void
    {
        $where1 = new Predicate();

        $where1->expression('some_expression()');

        self::assertSame(
            'SELECT "a_table".* FROM "a_table" WHERE (some_expression())',
            $this->makeSqlString($where1)
        );
    }

    /**
     * @throws ErrorException
     */
    public function testWillBindSqlParametersToExpressionsWithGivenParameter(): void
    {
        $where = new Predicate();

        $where->expression('some_expression(?)', null);

        self::assertSame(
            'SELECT "a_table".* FROM "a_table" WHERE (some_expression(\'\'))',
            $this->makeSqlString($where)
        );
    }

    /**
     * @throws ErrorException
     */
    public function testWillBindSqlParametersToExpressionsWithGivenStringParameter(): void
    {
        $where = new Predicate();

        $where->expression('some_expression(?)', 'a string');

        self::assertSame(
            'SELECT "a_table".* FROM "a_table" WHERE (some_expression(\'a string\'))',
            $this->makeSqlString($where)
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
        ErrorHandler::start(E_USER_NOTICE);

        try {
            $string = $select->getSqlString(new Sql92());
        } finally {
            ErrorHandler::stop();
        }

        return $string;
    }
}