<?php

declare(strict_types=1);

namespace PhpDbTest\Exception;

use PhpDb\Exception\ContainerException;
use PhpDb\Exception\ExceptionInterface;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;

use function sprintf;

#[Group('unit')]
#[CoversMethod(ContainerException::class, 'forService')]
final class ContainerExceptionTest extends TestCase
{
    #[Test]
    public function forServiceIsAPsrContainerException(): void
    {
        $exception = ContainerException::forService('Svc', 'Factory', 'reason');

        self::assertInstanceOf(ContainerExceptionInterface::class, $exception);
    }

    #[Test]
    public function forServiceIsReachableThroughThePackageMarkerInterface(): void
    {
        $exception = ContainerException::forService('Svc', 'Factory', 'reason');

        self::assertInstanceOf(ExceptionInterface::class, $exception);
    }

    #[Test]
    public function forServiceRendersItsTemplate(): void
    {
        $exception = ContainerException::forService('Svc', 'Factory', 'reason');

        self::assertSame(
            sprintf(ContainerException::SERVICE, 'Svc', 'Factory', 'reason'),
            $exception->getMessage(),
        );
    }
}
