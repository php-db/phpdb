<?php

declare(strict_types=1);

namespace PhpDb\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException as SplRuntimeException;

use function sprintf;

final class ContainerException extends SplRuntimeException implements ContainerExceptionInterface
{
    public static function forService(
        string $serviceName,
        string $factoryClass,
        string $reason
    ): self {
        return new self(
            sprintf(
                'Failed to create service "%s" in factory %s Reason: %s',
                $serviceName,
                $factoryClass,
                $reason
            ),
            0,
        );
    }
}
