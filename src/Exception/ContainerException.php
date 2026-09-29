<?php

declare(strict_types=1);

namespace PhpDb\Exception;

use Psr\Container\ContainerExceptionInterface;

use function sprintf;

final class ContainerException extends RuntimeException implements ContainerExceptionInterface
{
    /**
     * The ServiceManager dictates the thrown type here, and Laminas annotates
     * ServiceNotFoundException @final, so this package owns the wording without owning the
     * class.
     */
    final public const string MISSING_SERVICE = 'Service "%s" not found in container';

    final public const string MISSING_CONFIGURATION = 'No configuration found for %s';

    final public const string MISSING_CONFIG_SERVICE = 'Container is missing a config service';

    final public const string MISSING_DRIVER = 'no driver configured';

    final public const string NON_ADAPTER_AWARE_DELEGATE = 'Delegated service "%s" must implement %s';

    final public const string SERVICE = 'Failed to create service "%s" in factory %s Reason: %s';

    public static function forMissingConfigService(string $serviceName, string $factoryClass): self
    {
        return self::forService($serviceName, $factoryClass, self::MISSING_CONFIG_SERVICE);
    }

    public static function forMissingConfiguration(
        string $serviceName,
        string $factoryClass,
        string $requestedName,
    ): self {
        return self::forService($serviceName, $factoryClass, sprintf(self::MISSING_CONFIGURATION, $requestedName));
    }

    public static function forMissingDriver(string $serviceName, string $factoryClass): self
    {
        return self::forService($serviceName, $factoryClass, self::MISSING_DRIVER);
    }

    public static function forNonAdapterAwareDelegate(string $name, string $interface): self
    {
        return new self(sprintf(self::NON_ADAPTER_AWARE_DELEGATE, $name, $interface));
    }

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
        );
    }
}
