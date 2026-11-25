<?php

declare(strict_types=1);

namespace PhpDb\Container;

use Laminas\ServiceManager\ServiceManager;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\Profiler\ProfilerInterface;
use PhpDb\Exception\ContainerException;
use PhpDb\ResultSet\ResultSet;
use PhpDb\ResultSet\ResultSetInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class AdapterInterfaceFactory
{
    /**
     * @throws ContainerException If the config service, the adapter configuration or its driver is missing.
     * @throws ContainerExceptionInterface If a dependency cannot be built.
     * @throws NotFoundExceptionInterface If a dependency cannot be resolved.
     */
    public function __invoke(
        ContainerInterface&ServiceManager $container,
        string $requestedName,
    ): AdapterInterface&Adapter {
        if (! $container->has('config')) {
            throw ContainerException::forMissingConfigService($requestedName, self::class);
        }

        /** @var array<array-key, mixed> $config */
        $config = $container->get('config') ?? [];

        /** @var array{driver?: class-string<DriverInterface>, ...} $adapterConfig */
        $adapterConfig = $config[AdapterInterface::class] ?? $config[Adapter::class] ?? [];

        if ([] === $adapterConfig) {
            throw ContainerException::forMissingConfiguration(AdapterInterface::class, self::class, $requestedName);
        }

        $driverClass = $adapterConfig['driver'] ?? null;

        if (null === $driverClass) {
            throw ContainerException::forMissingDriver($requestedName, self::class);
        }

        $driver = $container->build($driverClass, $adapterConfig);

        $adapterPlatform = $container->build(PlatformInterface::class, ['driver' => $driver]);

        $profilerInterface = $container->has(ProfilerInterface::class)
            ? $container->build(ProfilerInterface::class)
            : null;

        $queryResultSetPrototype = $container->has(ResultSetInterface::class)
            ? $container->get(ResultSetInterface::class)
            : new ResultSet();

        return new Adapter(
            driver: $driver,
            platform: $adapterPlatform,
            queryResultSetPrototype: $queryResultSetPrototype,
            profiler: $profilerInterface,
        );
    }
}
