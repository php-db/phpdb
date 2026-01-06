<?php

declare(strict_types=1);

namespace PhpDb\Container;

use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Laminas\ServiceManager\ServiceManager;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\Profiler\ProfilerInterface;
use PhpDb\ConfigProvider;
use PhpDb\Exception\ContainerException;
use PhpDb\ResultSet\ResultSetInterface;
use Psr\Container\ContainerInterface;

use function is_array;

/**
 * Database adapter abstract service factory.
 *
 * Allows configuring several database instances (such as writer and reader).
 *
 * @internal
 */
class AbstractAdapterInterfaceFactory implements AbstractFactoryInterface
{
    protected ?array $config = null;

    /**
     * Can we create an adapter by the requested name?
     *
     * @param string $requestedName
     */
    public function canCreate(ContainerInterface $container, $requestedName): bool
    {
        $config = $this->getConfig($container);

        if ($config === []) {
            return false;
        }

        return isset($config[$requestedName])
            && is_array($config[$requestedName])
            && ! empty($config[$requestedName]);
    }

    /**
     * Create a DB adapter
     *
     * @phpstan-param ContainerInterface&ServiceManager $container
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface|ServiceManager $container,
        $requestedName,
        ?array $options = null
    ): AdapterInterface&Adapter {
        /** @var string|null $driverClass */
        $driverClass = $this->config[$requestedName]['driver'] ?? null;

        if ($driverClass === null) {
            throw ContainerException::forService(
                $requestedName,
                self::class,
                'no driver configured'
            );
        }

        /** @var DriverInterface|PdoDriverInterface $driver */
        $driver = $container->build($driverClass, $this->config[$requestedName]);
        /** @var PlatformInterface $platform */
        $platform = $container->build(PlatformInterface::class, ['driver' => $driver]);
        /** @var ResultSetInterface|null $resultSet */
        $resultSet = $container->has(ResultSetInterface::class)
            ? $container->build(ResultSetInterface::class)
            : null;
        /** @var ProfilerInterface|null $profiler */
        $profiler = $container->has(ProfilerInterface::class)
            ? $container->build(ProfilerInterface::class)
            : null;

        return match (true) {
            $resultSet !== null && $profiler !== null => new Adapter(
                driver: $driver,
                platform: $platform,
                queryResultSetPrototype: $resultSet,
                profiler: $profiler,
            ),
            $resultSet !== null => new Adapter(
                driver: $driver,
                platform: $platform,
                queryResultSetPrototype: $resultSet,
            ),
            $profiler !== null => new Adapter(
                driver: $driver,
                platform: $platform,
                profiler: $profiler,
            ),
            default => new Adapter(
                driver: $driver,
                platform: $platform,
            ),
        };
    }

    /**
     * Get db configuration, if any
     * todo: refactor to use PhpDb\ConfigProvider::NAMED_ADAPTER_KEY instead of hardcoding 'adapters'
     */
    protected function getConfig(ContainerInterface $container): array
    {
        if ($this->config !== null) {
            return $this->config;
        }

        if (! $container->has('config')) {
            $this->config = [];
            return $this->config;
        }

        $config       = $container->get('config');
        $this->config = $config[AdapterInterface::class][ConfigProvider::NAMED_ADAPTER_KEY] ?? [];
        return $this->config;
    }
}
