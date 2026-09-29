<?php

declare(strict_types=1);

namespace PhpDbTest\RowGateway\Exception;

use PhpDb\Exception\ExceptionInterface;
use PhpDb\RowGateway\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[Group('unit')]
#[CoversMethod(RuntimeException::class, 'forMissingPrimaryKeyColumn')]
#[CoversMethod(RuntimeException::class, 'forMissingPrimaryKeyData')]
#[CoversMethod(RuntimeException::class, 'forMissingSqlObject')]
#[CoversMethod(RuntimeException::class, 'forMissingTable')]
#[CoversMethod(RuntimeException::class, 'forUncallableMethod')]
final class RuntimeExceptionTest extends TestCase
{
    /** @return array<string, array{string, list<string>, string}> */
    public static function namedConstructorProvider(): array
    {
        return [
            'missing primary key column' => [
                'forMissingPrimaryKeyColumn',
                [],
                RuntimeException::MISSING_PRIMARY_KEY_COLUMN,
            ],
            'missing primary key data'   => [
                'forMissingPrimaryKeyData',
                ['id'],
                RuntimeException::MISSING_PRIMARY_KEY_DATA,
            ],
            'missing sql object'         => [
                'forMissingSqlObject',
                [],
                RuntimeException::MISSING_SQL_OBJECT,
            ],
            'missing table'              => [
                'forMissingTable',
                [],
                RuntimeException::MISSING_TABLE,
            ],
            'uncallable method'          => [
                'forUncallableMethod',
                [],
                RuntimeException::UNCALLABLE_METHOD,
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
