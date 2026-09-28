<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Exception;

use PhpDb\Exception\ExceptionInterface;
use PhpDb\Sql\Exception\RuntimeException;
use PhpDb\Sql\SqlInterface;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[Group('unit')]
#[CoversMethod(RuntimeException::class, 'forReplacementMismatch')]
#[CoversMethod(RuntimeException::class, 'forSubjectNotImplementing')]
#[CoversMethod(RuntimeException::class, 'forSubjectNotPreparableSqlInterface')]
#[CoversMethod(RuntimeException::class, 'forSubjectNotSqlInterface')]
#[CoversMethod(RuntimeException::class, 'forUnsupportedParameterCount')]
#[CoversMethod(RuntimeException::class, 'forUnsupportedParameterCountOf')]
final class RuntimeExceptionTest extends TestCase
{
    /** @return array<string, array{string, list<string|int>, string}> */
    public static function namedConstructorProvider(): array
    {
        return [
            'replacement mismatch'                 => [
                'forReplacementMismatch',
                [],
                RuntimeException::REPLACEMENT_MISMATCH,
            ],
            'subject not implementing'             => [
                'forSubjectNotImplementing',
                [SqlInterface::class, 'getSqlString()'],
                RuntimeException::SUBJECT_NOT_IMPLEMENTING,
            ],
            'subject not preparable sql interface' => [
                'forSubjectNotPreparableSqlInterface',
                [],
                RuntimeException::SUBJECT_NOT_PREPARABLE_SQL_INTERFACE,
            ],
            'subject not sql interface'            => [
                'forSubjectNotSqlInterface',
                [],
                RuntimeException::SUBJECT_NOT_SQL_INTERFACE,
            ],
            'unsupported parameter count'          => [
                'forUnsupportedParameterCount',
                [],
                RuntimeException::UNSUPPORTED_PARAMETER_COUNT,
            ],
            'unsupported parameter count of'       => [
                'forUnsupportedParameterCountOf',
                [2],
                RuntimeException::UNSUPPORTED_PARAMETER_COUNT_OF,
            ],
        ];
    }

    /** @param list<string|int> $arguments */
    #[Test]
    #[DataProvider('namedConstructorProvider')]
    public function namedConstructorRendersItsTemplate(string $method, array $arguments, string $template): void
    {
        $exception = RuntimeException::{$method}(...$arguments);

        self::assertSame(sprintf($template, ...$arguments), $exception->getMessage());
    }

    /** @param list<string|int> $arguments */
    #[Test]
    #[DataProvider('namedConstructorProvider')]
    public function namedConstructorReturnsTheComponentExceptionType(string $method, array $arguments): void
    {
        self::assertInstanceOf(ExceptionInterface::class, RuntimeException::{$method}(...$arguments));
    }
}
