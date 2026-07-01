<?php

declare(strict_types=1);

namespace PhpDbTest\Exception;

use PhpDb\Exception\ContainerException;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;

#[Group('unit')]
#[CoversMethod(ContainerException::class, 'forService')]
final class ContainerExceptionTest extends TestCase
{
    public function testForServiceCreatesFormattedExceptionMessage(): void
    {
        $exception = ContainerException::forService('Svc', 'Factory', 'reason');

        self::assertStringContainsString('Svc', $exception->getMessage());
        self::assertStringContainsString('Factory', $exception->getMessage());
        self::assertStringContainsString('reason', $exception->getMessage());
    }

    public function testImplementsContainerExceptionInterface(): void
    {
        $exception = ContainerException::forService('Svc', 'Factory', 'reason');

        self::assertInstanceOf(ContainerExceptionInterface::class, $exception);
    }
}
