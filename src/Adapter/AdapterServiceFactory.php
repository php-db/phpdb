<?php

namespace Laminas\Db\Adapter;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Override;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class AdapterServiceFactory implements FactoryInterface
{
    /**
     * Create db adapter service
     *
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param array|null         $options
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @return Adapter
     */
    #[Override] public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config = $container->get('config');
        return new Adapter($config['db']);
    }

    /**
     * Create db adapter service (v2)
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @return Adapter
     */
    #[Override] public function createService(ServiceLocatorInterface $serviceLocator): Adapter
    {
        return $this($serviceLocator, Adapter::class);
    }
}
