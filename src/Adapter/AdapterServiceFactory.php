<?php

declare(strict_types=1);

namespace PhpDb\Adapter;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Container\AdapterManager;
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
