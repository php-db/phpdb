<?php

declare(strict_types=1);

namespace Laminas\Db\Container;

use Laminas\Db\Adapter\AdapterInterface;
use Psr\Container\ContainerInterface;

final class AdapterInterfaceFactory
{
    public function __invoke(ContainerInterface $container): AdapterInterface
    {
        return ($container->get(AdapterManager::class))->get(AdapterInterface::class);
    }
}
