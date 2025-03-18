<?php

namespace Laminas\Db\Adapter;

use Laminas\Db\ResultSet;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
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
     *
     * @param  string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
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
     *
     * @param  string $requestedName
     * @param  array $options
     * @return Adapter
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config = $this->getConfig($container);

        return new Adapter(
            $config[$requestedName],
            $container->get(Driver\DriverInterface::class),
            $container->get(Platform\PlatformInterface::class),
            $container->has(ResultSet\ResultSetInterface::class) ? $container->get(ResultSet\ResultSetInterface::class) : new ResultSet\ResultSet(),
            $container->has(Profiler\ProfilerInterface::class) ? $container->get(Profiler\ProfilerInterface::class) : null
        );
    }

    /**
     * Get db configuration, if any
     *
     * @return array
     */
    protected function getConfig(ContainerInterface $container)
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
