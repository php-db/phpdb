<?php

namespace LaminasTest\Db\Sql\Predicate;

use ErrorException;
use Laminas\Db\Adapter\Platform\Sql92;
use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\ArgumentType;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Predicate\Predicate;
use Laminas\Db\Sql\Select;
use Laminas\Stdlib\ErrorHandler;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

use const E_USER_NOTICE;

final class PredicateTest extends TestCase
{
    public function testEqualToCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->equalTo('foo.bar', 'bar');

        $identifier = new Argument('foo.bar', ArgumentType::Identifier);
        $expression = new Argument('bar', ArgumentType::Value);

        $expressionData = $predicate->getExpressionData();

        self::assertCount(1, $expressionData);
        self::assertEquals('%s = %s', $expressionData->getExpressionSpecification());

        self::assertCount(2, $expressionData->getExpressionValues());
        self::assertEquals($identifier, $expressionData->getExpressionValues()[0]);
        self::assertEquals($expression, $expressionData->getExpressionValues()[1]);
    }

    public function testNotEqualToCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->notEqualTo('foo.bar', 'bar');

        $identifier = new Argument('foo.bar', ArgumentType::Identifier);
        $expression = new Argument('bar', ArgumentType::Value);

        $expressionData = $predicate->getExpressionData();

        self::assertEquals('%s != %s', $expressionData->getExpressionSpecification());
        self::assertCount(2, $expressionData->getExpressionValues());
        self::assertEquals($identifier, $expressionData->getExpressionValues()[0]);
        self::assertEquals($expression, $expressionData->getExpressionValues()[1]);
    }

    public function testLessThanCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->lessThan('foo.bar', 'bar');

        $identifier = new Argument('foo.bar', ArgumentType::Identifier);
        $expression = new Argument('bar', ArgumentType::Value);

        $expressionData = $predicate->getExpressionData();

        self::assertCount(1, $expressionData->getExpressionParts());
        self::assertEquals('%s < %s', $expressionData->getExpressionSpecification());

        self::assertCount(2, $expressionData->getExpressionValues());
        self::assertEquals($identifier, $expressionData->getExpressionValues()[0]);
        self::assertEquals($expression, $expressionData->getExpressionValues()[1]);
    }

    public function testGreaterThanCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->greaterThan('foo.bar', 'bar');

        $identifier = new Argument('foo.bar', ArgumentType::Identifier);
        $expression = new Argument('bar', ArgumentType::Value);

        $expressionData = $predicate->getExpressionData();

        self::assertCount(1, $expressionData->getExpressionParts());
        self::assertEquals('%s > %s', $expressionData->getExpressionSpecification());

        self::assertCount(2, $expressionData->getExpressionValues());
        self::assertEquals($identifier, $expressionData->getExpressionValues()[0]);
        self::assertEquals($expression, $expressionData->getExpressionValues()[1]);
    }

    public function testLessThanOrEqualToCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->lessThanOrEqualTo('foo.bar', 'bar');

        $identifier = new Argument('foo.bar', ArgumentType::Identifier);
        $expression = new Argument('bar', ArgumentType::Value);

        $expressionData = $predicate->getExpressionData();

        self::assertCount(1, $expressionData->getExpressionParts());
        self::assertEquals('%s <= %s', $expressionData->getExpressionSpecification());

        self::assertCount(2, $expressionData->getExpressionValues());
        self::assertEquals($identifier, $expressionData->getExpressionValues()[0]);
        self::assertEquals($expression, $expressionData->getExpressionValues()[1]);
    }

    public function testGreaterThanOrEqualToCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->greaterThanOrEqualTo('foo.bar', 'bar');

        $identifier = new Argument('foo.bar', ArgumentType::Identifier);
        $expression = new Argument('bar', ArgumentType::Value);

        $expressionData = $predicate->getExpressionData();

        self::assertCount(1, $expressionData->getExpressionParts());
        self::assertEquals('%s >= %s', $expressionData->getExpressionSpecification());

        self::assertCount(2, $expressionData->getExpressionValues());
        self::assertEquals($identifier, $expressionData->getExpressionValues()[0]);
        self::assertEquals($expression, $expressionData->getExpressionValues()[1]);
    }

    public function testLikeCreatesLikePredicate(): void
    {
        $predicate = new Predicate();
        $predicate->like('foo.bar', 'bar%');

        $identifier = new Argument('foo.bar', ArgumentType::Identifier);
        $expression = new Argument('bar%', ArgumentType::Value);

        $expressionData = $predicate->getExpressionData();

        self::assertCount(1, $expressionData->getExpressionParts());
        self::assertEquals('%1$s LIKE %2$s', $expressionData->getExpressionSpecification());

        self::assertCount(2, $expressionData->getExpressionValues());
        self::assertEquals($identifier, $expressionData->getExpressionValues()[0]);
        self::assertEquals($expression, $expressionData->getExpressionValues()[1]);
    }

    public function testNotLikeCreatesLikePredicate(): void
    {
        $predicate = new Predicate();
        $predicate->notLike('foo.bar', 'bar%');

        $identifier = new Argument('foo.bar', ArgumentType::Identifier);
        $expression = new Argument('bar%', ArgumentType::Value);

        $expressionData = $predicate->getExpressionData();

        self::assertCount(1, $expressionData->getExpressionParts());
        self::assertEquals('%1$s NOT LIKE %2$s', $expressionData->getExpressionSpecification());

        self::assertCount(2, $expressionData->getExpressionValues());
        self::assertEquals($identifier, $expressionData->getExpressionValues()[0]);
        self::assertEquals($expression, $expressionData->getExpressionValues()[1]);
    }

    public function testLiteralCreatesLiteralPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->literal('foo.bar = ?');
        $expressionData = $predicate->getExpressionData();

        self::assertCount(1, $expressionData->getExpressionParts());
        self::assertEquals('foo.bar = ?', $expressionData->getExpressionSpecification());
    }

    public function testIsNullCreatesIsNullPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->isNull('foo.bar');

        $identifier = new Argument('foo.bar', ArgumentType::Identifier);

        $expressionData = $predicate->getExpressionData();

        self::assertCount(1, $expressionData->getExpressionParts());
        self::assertEquals('%1$s IS NULL', $expressionData->getExpressionSpecification());

        self::assertCount(1, $expressionData->getExpressionValues());
        self::assertEquals($identifier, $expressionData->getExpressionValues()[0]);
    }

    public function testIsNotNullCreatesIsNotNullPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->isNotNull('foo.bar');

        $identifier = new Argument('foo.bar', ArgumentType::Identifier);

        $expressionData = $predicate->getExpressionData();

        self::assertCount(1, $expressionData->getExpressionParts());
        self::assertEquals('%1$s IS NOT NULL', $expressionData->getExpressionSpecification());

        self::assertCount(1, $expressionData->getExpressionValues());
        self::assertEquals($identifier, $expressionData->getExpressionValues()[0]);
    }

    public function testInCreatesInPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->in('foo.bar', ['foo', 'bar']);

        $identifier = new Argument('foo.bar', ArgumentType::Identifier);
        $expression = new Argument(['foo', 'bar'], ArgumentType::Value);

        $expressionData = $predicate->getExpressionData();

        self::assertCount(1, $expressionData->getExpressionParts());
        self::assertEquals('%s IN (%s, %s)', $expressionData->getExpressionSpecification());

        self::assertCount(2, $expressionData->getExpressionValues());
        self::assertEquals($identifier, $expressionData->getExpressionValues()[0]);
        self::assertEquals($expression, $expressionData->getExpressionValues()[1]);
    }

    public function testNotInCreatesNotInPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->notIn('foo.bar', ['foo', 'bar']);

        $identifier = new Argument('foo.bar', ArgumentType::Identifier);
        $expression = new Argument(['foo', 'bar'], ArgumentType::Value);

        $expressionData = $predicate->getExpressionData();

        self::assertCount(1, $expressionData->getExpressionParts());
        self::assertEquals('%s NOT IN (%s, %s)', $expressionData->getExpressionSpecification());

        self::assertCount(2, $expressionData->getExpressionValues());
        self::assertEquals($identifier, $expressionData->getExpressionValues()[0]);
        self::assertEquals($expression, $expressionData->getExpressionValues()[1]);
    }

    public function testBetweenCreatesBetweenPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->between('foo.bar', 1, 10);

        $identifier = new Argument('foo.bar', ArgumentType::Identifier);
        $minValue   = new Argument(1, ArgumentType::Value);
        $maxValue   = new Argument(10, ArgumentType::Value);

        $expressionData = $predicate->getExpressionData();

        self::assertCount(1, $expressionData->getExpressionParts());
        self::assertEquals('%1$s BETWEEN %2$s AND %3$s', $expressionData->getExpressionSpecification());

        self::assertCount(3, $expressionData->getExpressionValues());
        self::assertEquals($identifier, $expressionData->getExpressionValues()[0]);
        self::assertEquals($minValue, $expressionData->getExpressionValues()[1]);
        self::assertEquals($maxValue, $expressionData->getExpressionValues()[2]);
    }

    public function testBetweenCreatesNotBetweenPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->notBetween('foo.bar', 1, 10);

        $identifier = new Argument('foo.bar', ArgumentType::Identifier);
        $minValue   = new Argument(1, ArgumentType::Value);
        $maxValue   = new Argument(10, ArgumentType::Value);

        $expressionData = $predicate->getExpressionData();

        self::assertCount(1, $expressionData->getExpressionParts());
        self::assertEquals('%1$s NOT BETWEEN %2$s AND %3$s', $expressionData->getExpressionSpecification());

        self::assertCount(3, $expressionData->getExpressionValues());
        self::assertEquals($identifier, $expressionData->getExpressionValues()[0]);
        self::assertEquals($minValue, $expressionData->getExpressionValues()[1]);
        self::assertEquals($maxValue, $expressionData->getExpressionValues()[2]);
    }

    public function testCanChainPredicateFactoriesBetweenOperators(): void
    {
        $predicate = new Predicate();
        $predicate->isNull('foo.bar')
            ->or
            ->isNotNull('bar.baz')
            ->and
            ->equalTo('baz.bat', 'foo');

        $identifier1 = new Argument('foo.bar', ArgumentType::Identifier);
        $identifier2 = new Argument('bar.baz', ArgumentType::Identifier);
        $identifier3 = new Argument('baz.bat', ArgumentType::Identifier);
        $expression3 = new Argument('foo', ArgumentType::Value);

        $expressionData = $predicate->getExpressionData();

        self::assertCount(4, $expressionData->getExpressionValues());
        self::assertEquals('%1$s IS NULL', $expressionData->getExpressionPart(0)->getSpecificationString());
        self::assertEquals($identifier1, $expressionData->getExpressionValues()[0]);
        self::assertEquals('OR', $expressionData->getExpressionPart(1)->getSpecificationString());
        self::assertEquals('%1$s IS NOT NULL', $expressionData->getExpressionPart(2)->getSpecificationString());
        self::assertEquals($identifier2, $expressionData->getExpressionValues()[1]);
        self::assertEquals('AND', $expressionData->getExpressionPart(3)->getSpecificationString());
        self::assertEquals('%s = %s', $expressionData->getExpressionPart(4)->getSpecificationString());
        self::assertEquals($identifier3, $expressionData->getExpressionValues()[2]);
        self::assertEquals($expression3, $expressionData->getExpressionValues()[3]);
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

        $identifier1 = new Argument('foo.bar', ArgumentType::Identifier);
        $identifier2 = new Argument('bar.baz', ArgumentType::Identifier);
        $identifier3 = new Argument('baz.bat', ArgumentType::Identifier);
        $expression3 = new Argument('foo', ArgumentType::Value);

        $expressionData = $predicate->getExpressionData();

        self::assertCount(3, $expressionData->getExpressionParts());
        self::assertEquals('%1$s IS NULL', $expressionData->getExpressionPart(0)->getSpecificationString());
        self::assertEquals($identifier1, $expressionData->getExpressionValues()[0]);
        self::assertEquals('AND', $expressionData->getExpressionPart(1)->getSpecificationString());
        self::assertEquals('(%1$s IS NOT NULL AND %s = %s)', $expressionData->getExpressionPart(2)->getSpecificationString());
        self::assertEquals($identifier2, $expressionData->getExpressionValues()[1]);
        self::assertEquals($identifier3, $expressionData->getExpressionValues()[2]);
        self::assertEquals($expression3, $expressionData->getExpressionValues()[3]);
    }

    #[TestDox('Unit test: Test expression() is chainable and returns proper values')]
    public function testExpression(): void
    {
        $predicate  = new Predicate();
        $value = new Argument(0, ArgumentType::Value);

        // is chainable
        self::assertSame($predicate, $predicate->expression('foo = ?', 0));
        $expressionData = $predicate->getExpressionData();
        // with parameter
        self::assertEquals('foo = %s', $expressionData->getExpressionSpecification());
        self::assertEquals([$value], $expressionData->getExpressionValues());
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
        self::assertEquals('foo = bar', $expressionData->getExpressionSpecification());
        self::assertEquals([], $expressionData->getExpressionValues());

        // test literal() is backwards-compatible, and works with with parameters
        $predicate = new Predicate();
        $predicate->expression('foo = ?', 'bar');
        $expression = new Argument('bar', ArgumentType::Value);
        $expressionData = $predicate->getExpressionData();

        // with parameter
        self::assertEquals('foo = %s', $expressionData->getExpressionSpecification());
        self::assertEquals([$expression], $expressionData->getExpressionValues());

        // test literal() is backwards-compatible, and works with with parameters, even 0 which tests as false
        $predicate = new Predicate();
        $predicate->expression('foo = ?', 0);
        $expression = new Argument(0, ArgumentType::Value);
        $expressionData = $predicate->getExpressionData();

        // with parameter
        self::assertEquals('foo = %s', $expressionData->getExpressionSpecification());
        self::assertEquals([$expression], $expressionData->getExpressionValues());
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
