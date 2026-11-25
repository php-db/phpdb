<?php

declare(strict_types=1);

namespace PhpDb\Container;

use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Laminas\ServiceManager\ServiceManager;
use Override;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\Profiler\ProfilerInterface;
use PhpDb\ConfigProvider;
use PhpDb\Exception\ContainerException;
use PhpDb\ResultSet\ResultSet;
use PhpDb\ResultSet\ResultSetInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function is_array;

/**
 * Database adapter abstract service factory.
 *
 * Allows configuring several database instances (such as writer and reader).
 *
 * @internal
 */
final class AbstractAdapterInterfaceFactory implements AbstractFactoryInterface
{
    /** @var array<array-key, mixed>|null */
    protected ?array $config = null;

    /**
     * Can we create an adapter by the requested name?
     *
     * @param string $requestedName
     *
     * @throws ContainerExceptionInterface If retrieving the config service fails.
     * @throws NotFoundExceptionInterface If the config service cannot be resolved.
     */
    #[Override]
    public function canCreate(ContainerInterface $container, $requestedName): bool
    {
        $config = $this->getConfig($container);

        if ([] === $config) {
            return false;
        }

        return is_array($config[$requestedName] ?? null) && [] !== $config[$requestedName];
    }

    /**
     * Get db configuration, if any
     *
     * @return array<array-key, mixed>
     *
     * @throws ContainerExceptionInterface If retrieving the config service fails.
     * @throws NotFoundExceptionInterface If the config service cannot be resolved.
     */
    protected function getConfig(ContainerInterface $container): array
    {
        if (null !== $this->config) {
            return $this->config;
        }

        if (! $container->has('config')) {
            $this->config = [];
            return $this->config;
        }

        /** @var array{'PhpDb\Adapter\AdapterInterface'?: array{adapters?: array<array-key, mixed>, ...}, ...} $config */
        $config       = $container->get('config');
        $this->config = $config[AdapterInterface::class][ConfigProvider::NAMED_ADAPTER_KEY] ?? [];
        return $this->config;
    }

    /**
     * Create a "Named" DB adapter
     *
     * The adapter's dependencies are built per adapter, so the container must be a
     * ServiceManager.
     *
     * @param string $requestedName
     * @param array<array-key, mixed>|null $options
     *
     * @throws ContainerException If the named adapter has no driver configured.
     * @throws ContainerExceptionInterface If a dependency cannot be built.
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AdapterInterface&Adapter {
        /** @var array{driver?: class-string<DriverInterface>|null, ...} $adapterConfig */
        $adapterConfig = $this->config[$requestedName] ?? [];
        $driverClass   = $adapterConfig['driver'] ?? null;

        if (null === $driverClass) {
            throw ContainerException::forMissingDriver($requestedName, self::class);
        }
        if (! $container instanceof ServiceManager) {
            throw ContainerException::forNonBuildingContainer($requestedName, self::class, ServiceManager::class);
        }

        $driver = $container->build($driverClass, $adapterConfig);

        $platform = $container->build(PlatformInterface::class, ['driver' => $driver]);

        $resultSet = $container->has(ResultSetInterface::class)
            ? $container->build(ResultSetInterface::class)
            : new ResultSet();

        $profiler = $container->has(ProfilerInterface::class)
            ? $container->build(ProfilerInterface::class)
            : null;

        return new Adapter(
            driver: $driver,
            platform: $platform,
            queryResultSetPrototype: $resultSet,
            profiler: $profiler,
        );
    }
}
