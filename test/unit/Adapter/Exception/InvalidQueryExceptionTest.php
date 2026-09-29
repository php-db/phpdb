<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Exception;

use PhpDb\Adapter\Exception\InvalidQueryException;
use PhpDb\Exception\ExceptionInterface;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function sprintf;

#[Group('unit')]
#[CoversMethod(InvalidQueryException::class, 'forDriverError')]
#[CoversMethod(InvalidQueryException::class, 'forFailedStatement')]
final class InvalidQueryExceptionTest extends TestCase
{
    #[Test]
    public function forDriverErrorPassesTheDriverTextThrough(): void
    {
        $exception = InvalidQueryException::forDriverError('SQLSTATE[42S02]: Base table not found');

        self::assertSame(
            sprintf(InvalidQueryException::DRIVER_ERROR, 'SQLSTATE[42S02]: Base table not found'),
            $exception->getMessage(),
        );
    }

    #[Test]
    public function forFailedStatementKeepsTheCodeAndCause(): void
    {
        $cause     = new RuntimeException();
        $exception = InvalidQueryException::forFailedStatement('HY000', 42, $cause);

        self::assertSame(42, $exception->getCode());
        self::assertSame($cause, $exception->getPrevious());
    }

    #[Test]
    public function forFailedStatementRendersItsTemplate(): void
    {
        $exception = InvalidQueryException::forFailedStatement('HY000 - 1 - near "x"', 42, new RuntimeException());

        self::assertSame(
            sprintf(InvalidQueryException::FAILED_STATEMENT, 'HY000 - 1 - near "x"'),
            $exception->getMessage(),
        );
    }

    #[Test]
    public function isReachableThroughTheComponentExceptionInterface(): void
    {
        self::assertInstanceOf(ExceptionInterface::class, InvalidQueryException::forDriverError('x'));
    }
}
