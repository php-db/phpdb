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
}
