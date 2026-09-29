<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Exception;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\Feature\DriverFeatureProviderTrait;
use PhpDb\Adapter\Exception\RuntimeException;
use PhpDb\Adapter\Platform\Sql92;
use PhpDb\Exception\ExceptionInterface;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[Group('unit')]
#[CoversMethod(RuntimeException::class, 'forAlreadyPrepared')]
#[CoversMethod(RuntimeException::class, 'forDisconnectedRollback')]
#[CoversMethod(RuntimeException::class, 'forDriverError')]
#[CoversMethod(RuntimeException::class, 'forForwardOnlyRewind')]
#[CoversMethod(RuntimeException::class, 'forInvalidPdoParam')]
#[CoversMethod(RuntimeException::class, 'forMissingDsn')]
#[CoversMethod(RuntimeException::class, 'forMissingPdoExtension')]
#[CoversMethod(RuntimeException::class, 'forMissingQueryResult')]
#[CoversMethod(RuntimeException::class, 'forNonQueryResult')]
#[CoversMethod(RuntimeException::class, 'forRollbackWithoutTransaction')]
#[CoversMethod(RuntimeException::class, 'forUncomposableTrait')]
#[CoversMethod(RuntimeException::class, 'forUnstartedProfile')]
#[CoversMethod(RuntimeException::class, 'forVulnerablePlatformQuote')]
final class RuntimeExceptionTest extends TestCase
{
    /** @return array<string, array{string, list<string>, string}> */
    public static function namedConstructorProvider(): array
    {
        return [
            'already prepared'             => [
                'forAlreadyPrepared',
                [],
                RuntimeException::ALREADY_PREPARED,
            ],
            'disconnected rollback'        => [
                'forDisconnectedRollback',
                [],
                RuntimeException::DISCONNECTED_ROLLBACK,
            ],
            'driver error'                 => [
                'forDriverError',
                ['SQLSTATE[42S02]: Base table not found'],
                RuntimeException::DRIVER_ERROR,
            ],
            'forward only rewind'          => [
                'forForwardOnlyRewind',
                [],
                RuntimeException::FORWARD_ONLY_REWIND,
            ],
            'invalid pdo param'            => [
                'forInvalidPdoParam',
                ['bad-name'],
                RuntimeException::INVALID_PDO_PARAM,
            ],
            'missing dsn'                  => [
                'forMissingDsn',
                [],
                RuntimeException::MISSING_DSN,
            ],
            'missing pdo extension'        => [
                'forMissingPdoExtension',
                [],
                RuntimeException::MISSING_PDO_EXTENSION,
            ],
            'missing query result'         => [
                'forMissingQueryResult',
                [],
                RuntimeException::MISSING_QUERY_RESULT,
            ],
            'non query result'             => [
                'forNonQueryResult',
                [],
                RuntimeException::NON_QUERY_RESULT,
            ],
            'rollback without transaction' => [
                'forRollbackWithoutTransaction',
                [],
                RuntimeException::ROLLBACK_WITHOUT_TRANSACTION,
            ],
            'uncomposable trait'           => [
                'forUncomposableTrait',
                [DriverFeatureProviderTrait::class, DriverInterface::class],
                RuntimeException::UNCOMPOSABLE_TRAIT,
            ],
            'unstarted profile'            => [
                'forUnstartedProfile',
                ['profilerFinish'],
                RuntimeException::UNSTARTED_PROFILE,
            ],
            'vulnerable platform quote'    => [
                'forVulnerablePlatformQuote',
                [Sql92::class, 'Sql92::quoteValue'],
                RuntimeException::VULNERABLE_PLATFORM_QUOTE,
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
