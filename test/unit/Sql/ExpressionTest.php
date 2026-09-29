<?php

declare(strict_types=1);

namespace PhpDbTest\Sql;

use PhpDb\Sql\Argument;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\Exception\RuntimeException;
use PhpDb\Sql\Expression;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    #[DataProvider('falsyExpressionParametersProvider')]
    public function constructorWithFalsyValidParameters(mixed $falsyParameter): void
    {
        $expression = new Expression('?', $falsyParameter);
        $falsyValue = Argument::value($falsyParameter);

        $expressionData = $expression->getExpressionData();

        static::assertEquals([$falsyValue], $expressionData['values']);
    }

    #[Test]
    public function constructorWithInvalidParameter(): void
    {
        self::expectException(TypeError::class);
        new Expression('?', (object) []);
    }

    #[Test]
    public function constructorWithLiteralZero(): void
    {
        $expression = new Expression('0');
        static::assertSame('0', $expression->getExpression());
    }

    #[Test]
    public function constructorWithMultipleArguments(): void
    {
        $expression = new Expression('? + ? - ?', 1, 2, 3);

        $expressionData = $expression->getExpressionData();

        static::assertSame('%s + %s - %s', $expressionData['spec']);
        static::assertEquals(
            [
                Argument::value(1),
                Argument::value(2),
                Argument::value(3),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function getExpressionData(): void
    {
        $expression = new Expression(
            'X SAME AS ? AND Y = ? BUT LITERALLY ?',
            [
                new Argument\Identifier('foo'),
                new Argument\Value(5),
                new Argument\Literal('FUNC(FF%X)'),
            ],
        );

        $expressionData = $expression->getExpressionData();

        static::assertSame('X SAME AS %s AND Y = %s BUT LITERALLY %s', $expressionData['spec']);
        static::assertEquals(
            [
                new Identifier('foo'),
                new Value(5),
                new Literal('FUNC(FF%X)'),
            ],
            $expressionData['values'],
        );
    }

    #[Test]
    public function getExpressionDataThrowsExceptionWhenParameterCountMismatch(): void
    {
        $expression = new Expression('? AND ?', [1]); // Two placeholders but only one parameter

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(
            'The number of replacements in the expression does not match the number of parameters',
        );
        $expression->getExpressionData();
    }

    #[Test]
    public function getExpressionDataUsesRegexWhenPlaceholderCountMismatches(): void
    {
        $expression = new Expression('uf.user_id = :user_id OR uf.friend_id = :user_id', ['user_id' => 1]);

        $expressionData = $expression->getExpressionData();

        static::assertSame(
            'uf.user_id = :user_id OR uf.friend_id = :user_id',
            $expressionData['spec'],
        );
        static::assertCount(1, $expressionData['values']);
    }

    #[Test]
    public function getExpressionDataWillEscapePercent(): void
    {
        $expression = new Expression('X LIKE "foo%"');

        $expressionData = $expression->getExpressionData();

        static::assertSame('X LIKE "foo%%"', $expressionData['spec']);
    }

    #[Test]
    #[Group('7407')]
    public function getExpressionPreservesPercentageSignInFromUnixtime(): void
    {
        $expressionString = 'FROM_UNIXTIME(date, "%Y-%m")';
        $expression       = new Expression($expressionString);

        static::assertSame($expressionString, $expression->getExpression());
    }

    #[Test]
    public function numberOfReplacementsConsidersWhenSameVariableIsUsedManyTimes(): void
    {
        $expression = new Expression('uf.user_id = :user_id OR uf.friend_id = :user_id', ['user_id' => 1]);
        $value      = new Value(1);

        $expressionData = $expression->getExpressionData();

        static::assertSame(
            'uf.user_id = :user_id OR uf.friend_id = :user_id',
            $expressionData['spec'],
        );
        static::assertEquals([$value], $expressionData['values']);
    }

    #[Test]
    public function numberOfReplacementsForExpressionWithParameters(): void
    {
        $expression = new Expression(':a + :b', ['a' => 1, 'b' => 2]);
        $value1     = Argument::value(1);
        $value2     = Argument::value(2);

        $expressionData = $expression->getExpressionData();

        static::assertSame(':a + :b', $expressionData['spec']);
        static::assertEquals([$value1, $value2], $expressionData['values']);
    }

    #[Test]
    public function setExpression(): void
    {
        $expression = new Expression();

        // First mutation
        $result = $expression->setExpression('Foo Bar');

        // Verify fluent interface
        static::assertSame($expression, $result);

        // Verify the first mutation occurred
        static::assertSame('Foo Bar', $expression->getExpression());

        // Second mutation to verify mutability
        $expression->setExpression('Baz Qux');

        // Verify the instance was actually mutated
        static::assertSame('Baz Qux', $expression->getExpression());
    }

    #[Test]
    public function setExpressionException(): void
    {
        $expression = new Expression();
        self::expectException(TypeError::class);
        /** @noinspection PhpStrictTypeCheckingInspection */
        $expression->setExpression(null);

        $expression = new Expression();
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(InvalidArgumentException::EMPTY_EXPRESSION);
        $expression->setExpression('');
    }

    #[Test]
    public function setExpressionThrowsOnEmptyString(): void
    {
        $expression = new Expression();

        self::expectException(InvalidArgumentException::class);
        $expression->setExpression('');
    }

    #[Test]
    public function setParameters(): void
    {
        $expression = new Expression();

        // First mutation
        $result = $expression->setParameters('foo');

        // Verify fluent interface
        static::assertSame($expression, $result);

        // Verify the first mutation occurred
        static::assertEquals([new Value('foo')], $expression->getParameters());

        // Second mutation to verify mutability (setParameters appends)
        $expression->setParameters('bar');

        // Verify the instance was actually mutated (now has both parameters)
        static::assertEquals([new Value('foo'), new Value('bar')], $expression->getParameters());
    }

    #[Test]
    public function setParametersWrapsArrayInValuesArgument(): void
    {
        $expression = new Expression('? IN (?)', [Argument::identifier('id'), [1, 2, 3]]);

        $data = $expression->getExpressionData();

        static::assertCount(2, $data['values']);
        static::assertInstanceOf(Argument\Values::class, $data['values'][1]);
    }
}
