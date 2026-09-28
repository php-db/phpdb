<?php

declare(strict_types=1);

namespace PhpDb\Exception;

use Psr\Container\ContainerExceptionInterface;

use function sprintf;

final class ContainerException extends RuntimeException implements ContainerExceptionInterface
{
    final public const string SERVICE = 'Failed to create service "%s" in factory %s Reason: %s';

    public static function forService(
        string $serviceName,
        string $factoryClass,
        string $reason,
    ): self {
        return new self(
            sprintf(
                self::SERVICE,
                $serviceName,
                $factoryClass,
                $reason,
            ),
            0,
        );
    }
}
