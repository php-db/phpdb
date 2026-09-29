<?php

declare(strict_types=1);

namespace PhpDbTest\Metadata\Exception;

use PhpDb\Exception\ExceptionInterface;
use PhpDb\Metadata\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[Group('unit')]
#[CoversMethod(RuntimeException::class, 'forUnknownColumn')]
#[CoversMethod(RuntimeException::class, 'forUnknownConstraint')]
#[CoversMethod(RuntimeException::class, 'forUnknownTable')]
#[CoversMethod(RuntimeException::class, 'forUnknownTrigger')]
#[CoversMethod(RuntimeException::class, 'forUnknownView')]
#[CoversMethod(RuntimeException::class, 'forUnsupportedTableType')]
final class RuntimeExceptionTest extends TestCase
{
    /** @return array<string, array{string, list<string>, string}> */
    public static function namedConstructorProvider(): array
    {
        return [
            'unknown column'         => [
                'forUnknownColumn',
                [],
                RuntimeException::UNKNOWN_COLUMN,
            ],
            'unknown constraint'     => [
                'forUnknownConstraint',
                [],
                RuntimeException::UNKNOWN_CONSTRAINT,
            ],
            'unknown table'          => [
                'forUnknownTable',
                ['users'],
                RuntimeException::UNKNOWN_TABLE,
            ],
            'unknown trigger'        => [
                'forUnknownTrigger',
                ['after_insert'],
                RuntimeException::UNKNOWN_TRIGGER,
            ],
            'unknown view'           => [
                'forUnknownView',
                ['active_users'],
                RuntimeException::UNKNOWN_VIEW,
            ],
            'unsupported table type' => [
                'forUnsupportedTableType',
                ['users', 'SYSTEM VIEW'],
                RuntimeException::UNSUPPORTED_TABLE_TYPE,
            ],
        ];
    }

    /** @param list<string> $arguments */
    #[Test]
    #[DataProvider('namedConstructorProvider')]
    public function namedConstructorRendersItsTemplate(string $method, array $arguments, string $template): void
    {
        $exception = RuntimeException::{$method}(...$arguments);

        self::assertSame(sprintf($template, ...$arguments), $exception->getMessage());
    }

    /** @param list<string> $arguments */
    #[Test]
    #[DataProvider('namedConstructorProvider')]
    public function namedConstructorReturnsTheComponentExceptionType(string $method, array $arguments): void
    {
        self::assertInstanceOf(ExceptionInterface::class, RuntimeException::{$method}(...$arguments));
    }
}
