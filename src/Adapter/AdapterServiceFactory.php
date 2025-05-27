<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter;

use Laminas\Db\Container\AdapterManager;
use Psr\Container\ContainerInterface;

final class AdapterServiceFactory
{
    /**
     * Create db AdapterInterface instance
     * This is now managed by the AdapterManager
     * Satellite packages now delegate the creation context of the AdapterManager
     */
    public function __invoke(ContainerInterface $container): AdapterInterface {
        $adapterManager = $container->get(AdapterManager::class);
        return $adapterManager->get(AdapterInterface::class);
    }
}
