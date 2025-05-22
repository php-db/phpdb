<?php

namespace Laminas\Db\Adapter;

use _PHPStan_ac6dae9b0\Nette\DI\Config\Adapter;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Override;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

use Psr\Container\NotFoundExceptionInterface;

use function is_array;

/**
 * Database adapter abstract service factory.
 *
 * Allows configuring several database instances (such as writer and reader).
 */
abstract class AdapterAbstractServiceFactory implements AbstractFactoryInterface
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
     * @param string $requestedName
     * @return AdapterInterface
     */
    #[Override]
    abstract public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null
    ): AdapterInterface;

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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function createProfiler(ContainerInterface $container, array $parameters): ?Profiler\ProfilerInterface
    {
        $profilerInstance = $parameters['profiler'] ?? null;

        if ($profilerInstance !== null) {
            if ($profilerInstance === true) {
                $profilerInstance = Profiler\Profiler::class;
            }
            if (is_string($profilerInstance) && $container->has($profilerInstance)) {
                $profilerInstance = $container->build($profilerInstance);
            }
            if (! $profilerInstance instanceof Profiler\ProfilerInterface) {
                throw new Exception\InvalidArgumentException('Profiler must implement ProfilerInterface');
            }
            return $profilerInstance;
        }

        return null;
    }
}
