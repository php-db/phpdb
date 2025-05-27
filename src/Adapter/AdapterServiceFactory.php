<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Container\AdapterManager;
use Psr\Container\ContainerInterface;

final class AdapterServiceFactory
{
    /**
     * Create db adapter service
     */
    public function __invoke(ContainerInterface $container): AdapterInterface
    {
        $adapterManager = $container->get(AdapterManager::class);
        return $adapterManager->get(AdapterInterface::class);
    }
}
