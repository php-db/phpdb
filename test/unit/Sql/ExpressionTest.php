<?php

namespace LaminasTest\Db\Sql;

use Laminas\Db\Sql\Argument;
use Laminas\Db\Sql\ArgumentType;
use Laminas\Db\Sql\Exception\InvalidArgumentException;
use Laminas\Db\Sql\Expression;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * This is a unit testing test case.
 * A unit here is a method, there will be at least one test per method
 *
 * Expression is a value object with no dependencies/collaborators, therefore, no fixure needed
 */
#[CoversMethod(Expression::class, 'setExpression')]
#[CoversMethod(Expression::class, 'getExpression')]
#[CoversMethod(Expression::class, 'setParameters')]
#[CoversMethod(Expression::class, 'getParameters')]
#[CoversMethod(Expression::class, 'getExpressionData')]
final class ExpressionTest extends TestCase
{
    /**
     * @return Expression
     */
    public function testSetExpression()
    {
        $expression = new Expression();
        $return     = $expression->setExpression('Foo Bar');
        self::assertSame($expression, $return);
        return $return;
    }

    public function testSetExpressionException(): void
    {
        $expression = new Expression();
        $this->expectException(TypeError::class);
        /** @psalm-suppress NullArgument - ensure an exception is thrown */
        $expression->setExpression(null);

        $expression = new Expression();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Supplied expression must not be an empty string.');
        $expression->setExpression('');
    }

    #[Depends('testSetExpression')]
    public function testGetExpression(Expression $expression): void
    {
        self::assertEquals('Foo Bar', $expression->getExpression());
    }

    public function testSetParameters(): Expression
    {
        $expression = new Expression();
        $return     = $expression->setParameters('foo');
        self::assertSame($expression, $return);
        return $return;
    }

    #[Depends('testSetParameters')]
    public function testGetParameters(Expression $expression): void
    {
        self::assertEquals([Argument::value('foo')], $expression->getParameters());
    }

    public function testGetExpressionData(): void
    {
        $expression = new Expression(
            'X SAME AS ? AND Y = ? BUT LITERALLY ?',
            [
                ['foo' => ArgumentType::Identifier],
                [5 => ArgumentType::Value],
                ['FUNC(FF%X)' => ArgumentType::Literal],
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
        $value      = new Argument(1, ArgumentType::Value);

        $expressionData = $expression->getExpressionData();

        self::assertEquals('uf.user_id = :user_id OR uf.friend_id = :user_id', $expressionData->getExpressionSpecification());
        self::assertEquals([$value], $expressionData->getExpressionValues());
    }

    #[DataProvider('falsyExpressionParametersProvider')]
    public function testConstructorWithFalsyValidParameters(mixed $falsyParameter): void
    {
        $expression = new Expression('?', $falsyParameter);
        $falsyValue = new Argument($falsyParameter, ArgumentType::Value);

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
        $value1     = new Argument(1, ArgumentType::Value);
        $value2     = new Argument(2, ArgumentType::Value);

        $expressionData = $expression->getExpressionData();

        self::assertEquals(':a + :b', $expressionData->getExpressionSpecification());
        self::assertEquals([$value1, $value2], $expressionData->getExpressionValues());
    }
}
