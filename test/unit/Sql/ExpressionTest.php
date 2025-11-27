<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\Exception\RuntimeException;
use PhpDb\Sql\Expression;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * This is a unit testing test case.
 * A unit here is a method, there will be at least one test per method
 *
 * Expression is a value object with no dependencies/collaborators, therefore, no fixure needed
 */
#[CoversMethod(Expression::class, '__construct')]
#[CoversMethod(Expression::class, 'setExpression')]
#[CoversMethod(Expression::class, 'getExpression')]
#[CoversMethod(Expression::class, 'setParameters')]
#[CoversMethod(Expression::class, 'getParameters')]
#[CoversMethod(Expression::class, 'getExpressionData')]
final class ExpressionTest extends TestCase
{
    public function testSetExpression(): void
    {
        $expression = new Expression();

        // First mutation
        $result = $expression->setExpression('Foo Bar');

        // Verify fluent interface
        self::assertSame($expression, $result);

        // Verify the first mutation occurred
        self::assertEquals('Foo Bar', $expression->getExpression());

        // Second mutation to verify mutability
        $expression->setExpression('Baz Qux');

        // Verify the instance was actually mutated
        self::assertEquals('Baz Qux', $expression->getExpression());
    }

    public function testSetExpressionException(): void
    {
        $expression = new Expression();
        $this->expectException(TypeError::class);
        /** @noinspection PhpStrictTypeCheckingInspection */
        $expression->setExpression(null);

        $expression = new Expression();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Supplied expression must not be an empty string.');
        $expression->setExpression('');
    }

    public function testSetParameters(): void
    {
        $expression = new Expression();

        // First mutation
        $result = $expression->setParameters('foo');

        // Verify fluent interface
        self::assertSame($expression, $result);

        // Verify the first mutation occurred
        self::assertEquals([Argument::value('foo')], $expression->getParameters());

        // Second mutation to verify mutability (setParameters appends)
        $expression->setParameters('bar');

        // Verify the instance was actually mutated (now has both parameters)
        self::assertEquals([Argument::value('foo'), Argument::value('bar')], $expression->getParameters());
    }

    public function testGetExpressionData(): void
    {
        $expression = new Expression(
            'X SAME AS ? AND Y = ? BUT LITERALLY ?',
            [
                new Argument\Identifier('foo'),
                new Argument\Value(5),
                new Argument\Literal('FUNC(FF%X)'),
            ]
        );

        $expressionData = $expression->getExpressionData();

        self::assertEquals('X SAME AS %s AND Y = %s BUT LITERALLY %s', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::identifier('foo'),
            Argument::value(5),
            Argument::literal('FUNC(FF%X)'),
        ], $expressionData->getExpressionValues());
    }

    public function testGetExpressionDataWillEscapePercent(): void
    {
        $expression = new Expression('X LIKE "foo%"');

        $expressionData = $expression->getExpressionData();

        self::assertEquals('X LIKE "foo%%"', $expressionData->getExpressionSpecification());
    }

    public function testConstructorWithLiteralZero(): void
    {
        $expression = new Expression('0');
        self::assertSame('0', $expression->getExpression());
    }

    #[Group('7407')]
    public function testGetExpressionPreservesPercentageSignInFromUnixtime(): void
    {
        $expressionString = 'FROM_UNIXTIME(date, "%Y-%m")';
        $expression       = new Expression($expressionString);

        self::assertSame($expressionString, $expression->getExpression());
    }

    public function testNumberOfReplacementsConsidersWhenSameVariableIsUsedManyTimes(): void
    {
        $expression = new Expression('uf.user_id = :user_id OR uf.friend_id = :user_id', ['user_id' => 1]);
        $value      = Argument::value(1);

        $expressionData = $expression->getExpressionData();

        self::assertEquals(
            'uf.user_id = :user_id OR uf.friend_id = :user_id',
            $expressionData->getExpressionSpecification()
        );
        self::assertEquals([$value], $expressionData->getExpressionValues());
    }

    #[DataProvider('falsyExpressionParametersProvider')]
    public function testConstructorWithFalsyValidParameters(mixed $falsyParameter): void
    {
        $expression = new Expression('?', $falsyParameter);
        $falsyValue = Argument::value($falsyParameter);

        $expressionData = $expression->getExpressionData();

        self::assertEquals([$falsyValue], $expressionData->getExpressionValues());
    }

    public function testConstructorWithInvalidParameter(): void
    {
        $this->expectException(TypeError::class);
        new Expression('?', (object) []);
    }

    /** @psalm-return array<array-key, array{0: mixed}> */
    public static function falsyExpressionParametersProvider(): array
    {
        return [
            [''],
            ['0'],
            [0],
            [0.0],
            [false],
        ];
    }

    public function testNumberOfReplacementsForExpressionWithParameters(): void
    {
        $expression = new Expression(':a + :b', ['a' => 1, 'b' => 2]);
        $value1     = Argument::value(1);
        $value2     = Argument::value(2);

        $expressionData = $expression->getExpressionData();

        self::assertEquals(':a + :b', $expressionData->getExpressionSpecification());
        self::assertEquals([$value1, $value2], $expressionData->getExpressionValues());
    }

    public function testGetExpressionDataThrowsExceptionWhenParameterCountMismatch(): void
    {
        $expression = new Expression('? AND ?', [1]); // Two placeholders but only one parameter

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'The number of replacements in the expression does not match the number of parameters'
        );
        $expression->getExpressionData();
    }

    public function testConstructorWithMultipleArguments(): void
    {
        // Test deprecated multi-argument constructor
        $expression = new Expression('? + ? - ?', 1, 2, 3);

        $expressionData = $expression->getExpressionData();

        self::assertEquals('%s + %s - %s', $expressionData->getExpressionSpecification());
        self::assertEquals([
            Argument::value(1),
            Argument::value(2),
            Argument::value(3),
        ], $expressionData->getExpressionValues());
    }
}
