<?php

declare(strict_types=1);

namespace PhpDbTest\ResultSet\Exception;

use PhpDb\Exception\ExceptionInterface;
use PhpDb\ResultSet\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[CoversMethod(InvalidArgumentException::class, 'forUnresolvableIterator')]
#[CoversMethod(InvalidArgumentException::class, 'forNonIteratorDataSource')]
final class InvalidArgumentExceptionTest extends TestCase
{
    #[Test]
    public function forNonIteratorDataSourceNamesTheOffendingType(): void
    {
        self::assertStringContainsString(
            'DatePeriod',
            InvalidArgumentException::forNonIteratorDataSource('DatePeriod')->getMessage(),
        );
    }

    #[Test]
    public function forNonIteratorDataSourceReturnsTheComponentExceptionType(): void
    {
        self::assertInstanceOf(
            ExceptionInterface::class,
            InvalidArgumentException::forNonIteratorDataSource('DatePeriod'),
        );
    }

    #[Test]
    public function forUnresolvableIteratorNamesTheDepthItGaveUpAt(): void
    {
        self::assertStringContainsString('8', InvalidArgumentException::forUnresolvableIterator(8)->getMessage());
    }

    #[Test]
    public function forUnresolvableIteratorReturnsTheComponentExceptionType(): void
    {
        self::assertInstanceOf(ExceptionInterface::class, InvalidArgumentException::forUnresolvableIterator(8));
    }
}
