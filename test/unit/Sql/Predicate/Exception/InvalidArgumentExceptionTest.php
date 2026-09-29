<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate\Exception;

use PhpDb\Exception\ExceptionInterface;
use PhpDb\Sql\Predicate\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[Group('unit')]
#[CoversMethod(InvalidArgumentException::class, 'forInvalidCombination')]
#[CoversMethod(InvalidArgumentException::class, 'forMissingIdentifier')]
#[CoversMethod(InvalidArgumentException::class, 'forMissingLeftExpression')]
#[CoversMethod(InvalidArgumentException::class, 'forMissingLikeExpression')]
#[CoversMethod(InvalidArgumentException::class, 'forMissingMaxValue')]
#[CoversMethod(InvalidArgumentException::class, 'forMissingMinValue')]
#[CoversMethod(InvalidArgumentException::class, 'forMissingRightExpression')]
#[CoversMethod(InvalidArgumentException::class, 'forMissingValueSet')]
#[CoversMethod(InvalidArgumentException::class, 'forPredicateWithStringKey')]
final class InvalidArgumentExceptionTest extends TestCase
{
    /** @return array<string, array{string, list<string|int>, string}> */
    public static function namedConstructorProvider(): array
    {
        return [
            'invalid combination'       => [
                'forInvalidCombination',
                [],
                InvalidArgumentException::INVALID_COMBINATION,
            ],
            'missing identifier'        => [
                'forMissingIdentifier',
                [],
                InvalidArgumentException::MISSING_IDENTIFIER,
            ],
            'missing left expression'   => [
                'forMissingLeftExpression',
                [],
                InvalidArgumentException::MISSING_LEFT_EXPRESSION,
            ],
            'missing like expression'   => [
                'forMissingLikeExpression',
                [],
                InvalidArgumentException::MISSING_LIKE_EXPRESSION,
            ],
            'missing max value'         => [
                'forMissingMaxValue',
                [],
                InvalidArgumentException::MISSING_MAX_VALUE,
            ],
            'missing min value'         => [
                'forMissingMinValue',
                [],
                InvalidArgumentException::MISSING_MIN_VALUE,
            ],
            'missing right expression'  => [
                'forMissingRightExpression',
                [],
                InvalidArgumentException::MISSING_RIGHT_EXPRESSION,
            ],
            'missing value set'         => [
                'forMissingValueSet',
                [],
                InvalidArgumentException::MISSING_VALUE_SET,
            ],
            'predicate with string key' => [
                'forPredicateWithStringKey',
                [],
                InvalidArgumentException::PREDICATE_WITH_STRING_KEY,
            ],
        ];
    }

    /** @param list<string|int> $arguments */
    #[Test]
    #[DataProvider('namedConstructorProvider')]
    public function namedConstructorRendersItsTemplate(string $method, array $arguments, string $template): void
    {
        $exception = InvalidArgumentException::{$method}(...$arguments);

        self::assertSame(sprintf($template, ...$arguments), $exception->getMessage());
    }

    /** @param list<string|int> $arguments */
    #[Test]
    #[DataProvider('namedConstructorProvider')]
    public function namedConstructorReturnsTheComponentExceptionType(string $method, array $arguments): void
    {
        self::assertInstanceOf(ExceptionInterface::class, InvalidArgumentException::{$method}(...$arguments));
    }
}
