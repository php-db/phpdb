<?php

declare(strict_types=1);

namespace PhpDb\Container;

use Laminas\ServiceManager\ServiceManager;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\Profiler\ProfilerInterface;
use PhpDb\Exception\ContainerException;
use PhpDb\ResultSet\ResultSet;
use PhpDb\ResultSet\ResultSetInterface;
use Psr\Container\ContainerInterface;

final class AdapterInterfaceFactory
{
    public function __invoke(
        ContainerInterface&ServiceManager $container,
        string $requestedName,
    ): AdapterInterface&Adapter {
        if (! $container->has('config')) {
            throw ContainerException::forService(
                $requestedName,
                self::class,
                'Container is missing a config service'
            );
        }

        $config        = $container->get('config') ?? [];
        $adapterConfig = $config[AdapterInterface::class] ?? $config[Adapter::class] ?? [];

        if ($adapterConfig === []) {
            throw ContainerException::forService(
                AdapterInterface::class,
                self::class,
                'No configuration found for ' . $requestedName
            );
        }

        /** @var class-string<DriverInterface>|class-string<PdoDriverInterface>|null $driverClass */
        $driverClass = $adapterConfig['driver'] ?? null;

        /** @var DriverInterface|PdoDriverInterface $driver */
        $driver = $container->build($driverClass, $adapterConfig);

        /** @var PlatformInterface $adapterPlatform */
        $adapterPlatform = $container->build(PlatformInterface::class, ['driver' => $driver]);

        /** @var ProfilerInterface|null $profilerInterface */
        $profilerInterface = $container->has(ProfilerInterface::class)
            ? $container->build(ProfilerInterface::class)
            : null;

        /** @var ResultSetInterface|null $queryResultSetPrototype */
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
