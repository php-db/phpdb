<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Exception;

use PhpDb\Adapter\Exception\InvalidArgumentException;
use PhpDb\Exception\ExceptionInterface;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[Group('unit')]
#[CoversMethod(InvalidArgumentException::class, 'forIncorrectFlag')]
#[CoversMethod(InvalidArgumentException::class, 'forInvalidFetchMode')]
#[CoversMethod(InvalidArgumentException::class, 'forInvalidKeyType')]
#[CoversMethod(InvalidArgumentException::class, 'forInvalidMagicProperty')]
#[CoversMethod(InvalidArgumentException::class, 'forInvalidStatementMode')]
#[CoversMethod(InvalidArgumentException::class, 'forMissingData')]
final class InvalidArgumentExceptionTest extends TestCase
{
    /** @return array<string, array{string, list<string>, string}> */
    public static function namedConstructorProvider(): array
    {
        return [
            'incorrect flag'         => [
                'forIncorrectFlag',
                [],
                InvalidArgumentException::INCORRECT_FLAG,
            ],
            'invalid fetch mode'     => [
                'forInvalidFetchMode',
                [],
                InvalidArgumentException::INVALID_FETCH_MODE,
            ],
            'invalid key type'       => [
                'forInvalidKeyType',
                [],
                InvalidArgumentException::INVALID_KEY_TYPE,
            ],
            'invalid magic property' => [
                'forInvalidMagicProperty',
                [],
                InvalidArgumentException::INVALID_MAGIC_PROPERTY,
            ],
            'invalid statement mode' => [
                'forInvalidStatementMode',
                [],
                InvalidArgumentException::INVALID_STATEMENT_MODE,
            ],
            'missing data'           => [
                'forMissingData',
                [],
                InvalidArgumentException::MISSING_DATA,
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
