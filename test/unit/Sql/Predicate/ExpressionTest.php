<?php

namespace PhpDbTest\Sql\Predicate;

use PhpDb\Sql\Predicate\Expression;
use PhpDb\Sql\Predicate\IsNull;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function var_export;

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
        self::assertEquals('foo.bar = ?', $expression->getExpression());
        self::assertEquals(['bar'], $expression->getParameters());
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
        self::assertEquals([null], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassSingleZeroParameterValueToConstructor(): void
    {
        $predicate = new Expression('?', 0);
        self::assertEquals([0], $predicate->getParameters());
    }

    #[Group('6849')]
    public function testCanPassSinglePredicateParameterToConstructor(): void
    {
        $predicate  = new IsNull('foo.baz');
        $expression = new Expression('?', $predicate);
        self::assertEquals([$predicate], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassMultiScalarParametersToConstructor(): void
    {
        $expression = new Expression('? OR ?', 'foo', 'bar');
        self::assertEquals(['foo', 'bar'], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassMultiNullParametersToConstructor(): void
    {
        $expression = new Expression('? OR ?', null, null);
        self::assertEquals([null, null], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassMultiPredicateParametersToConstructor(): void
    {
        $predicate  = new IsNull('foo.baz');
        $expression = new Expression('? OR ?', $predicate, $predicate);
        self::assertEquals([$predicate, $predicate], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassArrayOfOneScalarParameterToConstructor(): void
    {
        $expression = new Expression('?', ['foo']);
        self::assertEquals(['foo'], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassArrayOfMultiScalarsParameterToConstructor(): void
    {
        $expression = new Expression('? OR ?', ['foo', 'bar']);
        self::assertEquals(['foo', 'bar'], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassArrayOfOneNullParameterToConstructor(): void
    {
        $expression = new Expression('?', [null]);
        self::assertEquals([null], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassArrayOfMultiNullsParameterToConstructor(): void
    {
        $expression = new Expression('? OR ?', [null, null]);
        self::assertEquals([null, null], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassArrayOfOnePredicateParameterToConstructor(): void
    {
        $predicate  = new IsNull('foo.baz');
        $expression = new Expression('?', [$predicate]);
        self::assertEquals([$predicate], $expression->getParameters());
    }

    #[Group('6849')]
    public function testCanPassArrayOfMultiPredicatesParameterToConstructor(): void
    {
        $predicate  = new IsNull('foo.baz');
        $expression = new Expression('? OR ?', [$predicate, $predicate]);
        self::assertEquals([$predicate, $predicate], $expression->getParameters());
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
        self::assertEquals(['foo', 'bar'], $expression->getParameters());
    }

    public function testRetrievingWherePartsReturnsSpecificationArrayOfLiteralAndParametersAndArrayOfTypes(): void
    {
        $expression = new Expression();
        $expression->setExpression('foo.bar = ? AND id != ?')
                        ->setParameters(['foo', 'bar']);
        $expected = [
            [
                'foo.bar = %s AND id != %s',
                ['foo', 'bar'],
                [Expression::TYPE_VALUE, Expression::TYPE_VALUE],
            ],
        ];
        $test     = $expression->getExpressionData();
        self::assertEquals($expected, $test, var_export($test, true));
    }
}
