<?php

namespace PhpDb\Adapter;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class AdapterServiceDelegator
{
    /** @var string */
    private $adapterName;

    public function __construct(string $adapterName = AdapterInterface::class)
    {
        $this->adapterName = $adapterName;
    }

    public static function __set_state(array $state): self
    {
        return new self($state['adapterName'] ?? AdapterInterface::class);
    }

    /**
     * @param ContainerInterface $container
     * @param string             $name
     * @param callable           $callback
     * @param array|null         $options
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @return AdapterInterface
     */
    public function __invoke(
        ContainerInterface $container,
        string $name,
        callable $callback,
        ?array $options = null
    ) {
        $instance = $callback();

        if (! $instance instanceof AdapterAwareInterface || ! $container->has($this->adapterName)) {
            return $instance;
        }

        $databaseAdapter = $container->get($this->adapterName);

        if (! $databaseAdapter instanceof AdapterInterface) {
            return $instance;
        }

        $instance->setDbAdapter($databaseAdapter);

        return $instance;
    }
}
