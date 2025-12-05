<?php

declare(strict_types=1);

namespace PhpDb\Container;

use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use PhpDb\Adapter\AdapterAwareInterface;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function sprintf;

class AdapterServiceDelegator
{
    public function __construct(
        protected readonly string $adapterName = AdapterInterface::class
    ) {
    }

    public static function __set_state(array $state): self
    {
        return new self($state['adapterName'] ?? AdapterInterface::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(
        ContainerInterface $container,
        string $name,
        callable $callback,
        ?array $options = null
    ): AdapterAwareInterface {
        $instance = $callback();

        if (! $instance instanceof AdapterAwareInterface) {
            throw new Exception\RuntimeException(sprintf(
                'Delegated service "%s" must implement %s',
                $name,
                AdapterAwareInterface::class
            ));
        }

        if (! $container->has($this->adapterName)) {
            throw new ServiceNotFoundException(sprintf(
                'Service "%s" not found in container',
                $this->adapterName
            ));
        }

        $databaseAdapter = $container->get($this->adapterName);

        if (! $databaseAdapter instanceof AdapterInterface) {
            return $instance;
        }

        $instance->setDbAdapter($databaseAdapter);

        return $instance;
    }
}
