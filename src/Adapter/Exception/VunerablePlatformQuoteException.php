<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Exception;

use function sprintf;

final class VunerablePlatformQuoteException extends RuntimeException implements ExceptionInterface
{
    public static function forPlatformAndMethod(string $platformName, string $methodName): self
    {
        return new self(
            sprintf(
                'Attempting to quote in %s::%s without extension/driver support'
                    . ' can introduce security vulnerabilities in a production environment.',
                $platformName,
                $methodName
            ),
        );
    }
}
