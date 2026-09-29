<?php

declare(strict_types=1);

namespace PhpDbTest\Sql\Predicate\Exception;

use PhpDb\Exception\ExceptionInterface;
use PhpDb\Sql\Predicate\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[Group('unit')]
#[CoversMethod(RuntimeException::class, 'forNotNested')]
final class RuntimeExceptionTest extends TestCase
{
    /** @return array<string, array{string, list<string|int>, string}> */
    public static function namedConstructorProvider(): array
    {
        return [
            'not nested' => [
                'forNotNested',
                [],
                RuntimeException::NOT_NESTED,
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
