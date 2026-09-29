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
#[CoversMethod(ContainerException::class, 'forMissingConfigService')]
#[CoversMethod(ContainerException::class, 'forMissingConfiguration')]
#[CoversMethod(ContainerException::class, 'forMissingDriver')]
#[CoversMethod(ContainerException::class, 'forNonAdapterAwareDelegate')]
#[CoversMethod(ContainerException::class, 'forService')]
final class ContainerExceptionTest extends TestCase
{
    #[Test]
    public function forMissingConfigServiceComposesTheReasonIntoTheServiceTemplate(): void
    {
        self::assertSame(
            sprintf(
                ContainerException::SERVICE,
                'Svc',
                'Factory',
                ContainerException::MISSING_CONFIG_SERVICE,
            ),
            ContainerException::forMissingConfigService('Svc', 'Factory')->getMessage(),
        );
    }

    #[Test]
    public function forMissingConfigurationNamesTheRequestedService(): void
    {
        self::assertSame(
            sprintf(
                ContainerException::SERVICE,
                'Svc',
                'Factory',
                sprintf(ContainerException::MISSING_CONFIGURATION, 'Requested'),
            ),
            ContainerException::forMissingConfiguration('Svc', 'Factory', 'Requested')->getMessage(),
        );
    }

    #[Test]
    public function forMissingDriverComposesTheReasonIntoTheServiceTemplate(): void
    {
        self::assertSame(
            sprintf(ContainerException::SERVICE, 'Svc', 'Factory', ContainerException::MISSING_DRIVER),
            ContainerException::forMissingDriver('Svc', 'Factory')->getMessage(),
        );
    }

    #[Test]
    public function forNonAdapterAwareDelegateRendersItsTemplate(): void
    {
        self::assertSame(
            sprintf(ContainerException::NON_ADAPTER_AWARE_DELEGATE, 'Svc', 'SomeInterface'),
            ContainerException::forNonAdapterAwareDelegate('Svc', 'SomeInterface')->getMessage(),
        );
    }

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
