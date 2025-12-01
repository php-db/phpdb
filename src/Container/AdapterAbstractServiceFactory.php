<?php

declare(strict_types=1);

namespace PhpDb\Container;

use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\ResultSet\ResultSetInterface;
use Psr\Container\ContainerInterface;

use function is_array;

/**
 * Database adapter abstract service factory.
 *
 * Allows configuring several database instances (such as writer and reader).
 */
class AdapterAbstractServiceFactory implements AbstractFactoryInterface
{
    /** @var array */
    protected $config;

    /**
     * Can we create an adapter by the requested name?
     */
    public function canCreate(ContainerInterface $container, $requestedName): bool
    {
        $config = $this->getConfig($container);
        if (empty($config)) {
            return false;
        }

        return isset($config[$requestedName])
            && is_array($config[$requestedName])
            && ! empty($config[$requestedName]);
    }

    /**
     * Create a DB adapter
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null
    ): AdapterInterface {
        $driverFactory   = ($container->get(DriverInterfaceFactoryFactoryInterface::class))($container, $requestedName);
        $driverInstance  = $driverFactory::createFromConfig($container, $requestedName);
        $platformFactory = ($container->get(PlatformInterfaceFactoryFactoryInterface::class))();

        return new Adapter(
            $driverInstance,
            $platformFactory::fromDriver($driverInstance),
            $container->get(ResultSetInterface::class),
        );
    }

    /**
     * Get db configuration, if any
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

        $config = $container->get('config');
        if (
            ! isset($config['db'])
            || ! is_array($config['db'])
        ) {
            $this->config = [];
            return $this->config;
        }

        $config = $config['db'];
        if (
            ! isset($config['adapters'])
            || ! is_array($config['adapters'])
        ) {
            $this->config = [];
            return $this->config;
        }

        $this->config = $config['adapters'];
        return $this->config;
    }
}
