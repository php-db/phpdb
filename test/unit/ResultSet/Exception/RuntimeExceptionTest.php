<?php

declare(strict_types=1);

namespace PhpDbTest\ResultSet\Exception;

use PhpDb\Exception\ExceptionInterface;
use PhpDb\ResultSet\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[CoversMethod(RuntimeException::class, 'forUnbufferedIteration')]
#[CoversMethod(RuntimeException::class, 'forUninitialisedDataSource')]
#[CoversMethod(RuntimeException::class, 'forUnhydratableRow')]
final class RuntimeExceptionTest extends TestCase
{
    #[Test]
    public function forUnbufferedIterationRendersItsTemplate(): void
    {
        self::assertSame(
            RuntimeException::UNBUFFERED_ITERATION,
            RuntimeException::forUnbufferedIteration()->getMessage(),
        );
    }

    #[Test]
    public function forUnbufferedIterationReturnsTheComponentExceptionType(): void
    {
        self::assertInstanceOf(ExceptionInterface::class, RuntimeException::forUnbufferedIteration());
    }

    #[Test]
    public function forUnhydratableRowNamesTheOffendingType(): void
    {
        self::assertStringContainsString(
            'bool',
            RuntimeException::forUnhydratableRow('bool')->getMessage(),
        );
    }

    #[Test]
    public function forUnhydratableRowReturnsTheComponentExceptionType(): void
    {
        self::assertInstanceOf(ExceptionInterface::class, RuntimeException::forUnhydratableRow('bool'));
    }

    #[Test]
    public function forUninitialisedDataSourceRendersItsTemplate(): void
    {
        self::assertSame(
            RuntimeException::UNINITIALISED_DATA_SOURCE,
            RuntimeException::forUninitialisedDataSource()->getMessage(),
        );
    }

    #[Test]
    public function forUninitialisedDataSourceReturnsTheComponentExceptionType(): void
    {
        self::assertInstanceOf(ExceptionInterface::class, RuntimeException::forUninitialisedDataSource());
    }
}
