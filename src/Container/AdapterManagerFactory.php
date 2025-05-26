<?php

declare(strict_types=1);

namespace Laminas\Db\Container;

use Laminas\Db\Adapter\ConfigInterface;
use Psr\Container\ContainerInterface;

final class AdapterManagerFactory
{
    public function __invoke(ContainerInterface $container): AdapterManager
    {
        // todo: get defensive here
        $config   = $container->get('config');
        $dbConfig = $config['db'] ?? [];

        return new AdapterManager(
            $container,
            [
                'aliases' => [
                    'db' => ConfigInterface::class,
                ],
                'services' => [
                    ConfigInterface::class => $dbConfig,
                ],
            ]
        );
    }
}
