<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Exception;

use PhpDb\Adapter\Exception\VunerablePlatformQuoteException;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[CoversMethod(VunerablePlatformQuoteException::class, 'forPlatformAndMethod')]
final class VunerablePlatformQuoteExceptionTest extends TestCase
{
    /**
     * The message has to name the offending platform and method before it explains the
     * risk, so the reader knows what to go and look at.
     */
    #[Test]
    public function forPlatformAndMethodNamesThePlatformAndMethodAheadOfTheWarning(): void
    {
        $exception = VunerablePlatformQuoteException::forPlatformAndMethod('Sql92', 'quoteValue');

        static::assertSame(
            'Attempting to quote in Sql92::quoteValue without extension/driver support'
                . ' can introduce security vulnerabilities in a production environment.',
            $exception->getMessage(),
        );
    }
}
