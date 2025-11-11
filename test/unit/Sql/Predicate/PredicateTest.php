<?php

namespace PhpDbTest\Sql\Predicate;

use ErrorException;
use Laminas\Stdlib\ErrorHandler;
use PhpDb\Adapter\Platform\Sql92;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Expression;
use PhpDb\Sql\ExpressionInterface;
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
        $parts = $predicate->getExpressionData();
        $this->assertIsArray($parts[0]);
        self::assertCount(1, $parts);
        self::assertContains('%s = %s', $parts[0]);
        self::assertContains(['foo.bar', 'bar'], $parts[0]);
    }

    public function testNotEqualToCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->notEqualTo('foo.bar', 'bar');
        $parts = $predicate->getExpressionData();
        $this->assertIsArray($parts[0]);
        self::assertCount(1, $parts);
        self::assertContains('%s != %s', $parts[0]);
        self::assertContains(['foo.bar', 'bar'], $parts[0]);
    }

    public function testLessThanCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->lessThan('foo.bar', 'bar');
        $parts = $predicate->getExpressionData();
        $this->assertIsArray($parts[0]);
        self::assertCount(1, $parts);
        self::assertContains('%s < %s', $parts[0]);
        self::assertContains(['foo.bar', 'bar'], $parts[0]);
    }

    public function testGreaterThanCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->greaterThan('foo.bar', 'bar');
        $parts = $predicate->getExpressionData();
        $this->assertIsArray($parts[0]);
        self::assertCount(1, $parts);
        self::assertContains('%s > %s', $parts[0]);
        self::assertContains(['foo.bar', 'bar'], $parts[0]);
    }

    public function testLessThanOrEqualToCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->lessThanOrEqualTo('foo.bar', 'bar');
        $parts = $predicate->getExpressionData();
        $this->assertIsArray($parts[0]);
        self::assertCount(1, $parts);
        self::assertContains('%s <= %s', $parts[0]);
        self::assertContains(['foo.bar', 'bar'], $parts[0]);
    }

    public function testGreaterThanOrEqualToCreatesOperatorPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->greaterThanOrEqualTo('foo.bar', 'bar');
        $parts = $predicate->getExpressionData();
        $this->assertIsArray($parts[0]);
        self::assertCount(1, $parts);
        self::assertContains('%s >= %s', $parts[0]);
        self::assertContains(['foo.bar', 'bar'], $parts[0]);
    }

    public function testLikeCreatesLikePredicate(): void
    {
        $predicate = new Predicate();
        $predicate->like('foo.bar', 'bar%');
        $parts = $predicate->getExpressionData();
        $this->assertIsArray($parts[0]);
        self::assertCount(1, $parts);
        self::assertContains('%1$s LIKE %2$s', $parts[0]);
        self::assertContains(['foo.bar', 'bar%'], $parts[0]);
    }

    public function testNotLikeCreatesLikePredicate(): void
    {
        $predicate = new Predicate();
        $predicate->notLike('foo.bar', 'bar%');
        $parts = $predicate->getExpressionData();
        $this->assertIsArray($parts[0]);
        self::assertCount(1, $parts);
        self::assertContains('%1$s NOT LIKE %2$s', $parts[0]);
        self::assertContains(['foo.bar', 'bar%'], $parts[0]);
    }

    public function testLiteralCreatesLiteralPredicate(): void
    {
        $predicate = new Predicate();
        /** @psalm-suppress TooManyArguments */
        $predicate->literal('foo.bar = ?', 'bar');
        $parts = $predicate->getExpressionData();
        $this->assertIsArray($parts[0]);
        self::assertCount(1, $parts);
        self::assertContains('foo.bar = %s', $parts[0]);
        self::assertContains(['bar'], $parts[0]);
    }

    public function testIsNullCreatesIsNullPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->isNull('foo.bar');
        $parts = $predicate->getExpressionData();
        $this->assertIsArray($parts[0]);
        self::assertCount(1, $parts);
        self::assertContains('%1$s IS NULL', $parts[0]);
        self::assertContains(['foo.bar'], $parts[0]);
    }

    public function testIsNotNullCreatesIsNotNullPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->isNotNull('foo.bar');
        $parts = $predicate->getExpressionData();
        $this->assertIsArray($parts[0]);
        self::assertCount(1, $parts);
        self::assertContains('%1$s IS NOT NULL', $parts[0]);
        self::assertContains(['foo.bar'], $parts[0]);
    }

    public function testInCreatesInPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->in('foo.bar', ['foo', 'bar']);
        $parts = $predicate->getExpressionData();
        $this->assertIsArray($parts[0]);
        self::assertCount(1, $parts);
        self::assertContains('%s IN (%s, %s)', $parts[0]);
        self::assertContains(['foo.bar', 'foo', 'bar'], $parts[0]);
    }

    public function testNotInCreatesNotInPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->notIn('foo.bar', ['foo', 'bar']);
        $parts = $predicate->getExpressionData();
        $this->assertIsArray($parts[0]);
        self::assertCount(1, $parts);
        self::assertContains('%s NOT IN (%s, %s)', $parts[0]);
        self::assertContains(['foo.bar', 'foo', 'bar'], $parts[0]);
    }

    public function testBetweenCreatesBetweenPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->between('foo.bar', 1, 10);
        $parts = $predicate->getExpressionData();
        $this->assertIsArray($parts[0]);
        self::assertCount(1, $parts);
        self::assertContains('%1$s BETWEEN %2$s AND %3$s', $parts[0]);
        self::assertContains(['foo.bar', 1, 10], $parts[0]);
    }

    public function testBetweenCreatesNotBetweenPredicate(): void
    {
        $predicate = new Predicate();
        $predicate->notBetween('foo.bar', 1, 10);
        $parts = $predicate->getExpressionData();
        $this->assertIsArray($parts[0]);
        self::assertCount(1, $parts);
        self::assertContains('%1$s NOT BETWEEN %2$s AND %3$s', $parts[0]);
        self::assertContains(['foo.bar', 1, 10], $parts[0]);
    }

    public function testCanChainPredicateFactoriesBetweenOperators(): void
    {
        $predicate = new Predicate();
        $predicate->isNull('foo.bar')
                  ->or
                  ->isNotNull('bar.baz')
                  ->and
                  ->equalTo('baz.bat', 'foo');
        $parts = $predicate->getExpressionData();
        $this->assertIsArray($parts[0]);
        self::assertCount(5, $parts);

        self::assertContains('%1$s IS NULL', $parts[0]);
        self::assertContains(['foo.bar'], $parts[0]);

        self::assertEquals(' OR ', $parts[1]);

        $this->assertIsArray($parts[2]);
        self::assertContains('%1$s IS NOT NULL', $parts[2]);
        self::assertContains(['bar.baz'], $parts[2]);

        self::assertEquals(' AND ', $parts[3]);

        $this->assertIsArray($parts[4]);
        self::assertContains('%s = %s', $parts[4]);
        self::assertContains(['baz.bat', 'foo'], $parts[4]);
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
        $parts = $predicate->getExpressionData();

        self::assertCount(7, $parts);

        $this->assertIsArray($parts[0]);
        self::assertContains('%1$s IS NULL', $parts[0]);
        self::assertContains(['foo.bar'], $parts[0]);

        self::assertEquals(' AND ', $parts[1]);

        self::assertEquals('(', $parts[2]);

        $this->assertIsArray($parts[3]);
        self::assertContains('%1$s IS NOT NULL', $parts[3]);
        self::assertContains(['bar.baz'], $parts[3]);

        self::assertEquals(' AND ', $parts[4]);

        $this->assertIsArray($parts[5]);
        self::assertContains('%s = %s', $parts[5]);
        self::assertContains(['baz.bat', 'foo'], $parts[5]);

        self::assertEquals(')', $parts[6]);
    }

    #[TestDox('Unit test: Test expression() is chainable and returns proper values')]
    public function testExpression(): void
    {
        $predicate = new Predicate();

        // is chainable
        self::assertSame($predicate, $predicate->expression('foo = ?', 0));
        // with parameter
        self::assertEquals(
            [['foo = %s', [0], [ArgumentType::Value]]],
            $predicate->getExpressionData()
        );
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
        // with parameter
        self::assertEquals(
            [['foo = bar', [], []]],
            $predicate->getExpressionData()
        );

        // test literal() is backwards-compatible, and works with with parameters
        $predicate = new Predicate();
        $predicate->expression('foo = ?', 'bar');
        // with parameter
        self::assertEquals(
            [['foo = %s', ['bar'], [ArgumentType::Value]]],
            $predicate->getExpressionData()
        );

        // test literal() is backwards-compatible, and works with with parameters, even 0 which tests as false
        $predicate = new Predicate();
        $predicate->expression('foo = ?', 0);
        // with parameter
        self::assertEquals(
            [['foo = %s', [0], [ArgumentType::Value]]],
            $predicate->getExpressionData()
        );
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
