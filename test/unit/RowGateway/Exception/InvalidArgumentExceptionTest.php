<?php

declare(strict_types=1);

namespace PhpDbTest\RowGateway\Exception;

use PhpDb\Exception\ExceptionInterface;
use PhpDb\RowGateway\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[Group('unit')]
#[CoversMethod(InvalidArgumentException::class, 'forInvalidColumn')]
#[CoversMethod(InvalidArgumentException::class, 'forSqlTableMismatch')]
final class InvalidArgumentExceptionTest extends TestCase
{
    /** @return array<string, array{string, list<string>, string}> */
    public static function namedConstructorProvider(): array
    {
        return [
            'invalid column'     => [
                'forInvalidColumn',
                ['email'],
                InvalidArgumentException::INVALID_COLUMN,
            ],
            'sql table mismatch' => [
                'forSqlTableMismatch',
                [],
                InvalidArgumentException::SQL_TABLE_MISMATCH,
            ],
        ];
    }

    /** @param list<string> $arguments */
    #[Test]
    #[DataProvider('namedConstructorProvider')]
    public function namedConstructorRendersItsTemplate(string $method, array $arguments, string $template): void
    {
        $exception = InvalidArgumentException::{$method}(...$arguments);

        self::assertSame(sprintf($template, ...$arguments), $exception->getMessage());
    }

    /** @param list<string> $arguments */
    #[Test]
    #[DataProvider('namedConstructorProvider')]
    public function namedConstructorReturnsTheComponentExceptionType(string $method, array $arguments): void
    {
        self::assertInstanceOf(ExceptionInterface::class, InvalidArgumentException::{$method}(...$arguments));
    }
}
