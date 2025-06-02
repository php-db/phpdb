<?php

declare(strict_types=1);

namespace Laminas\Db\Container;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\AdapterServiceFactory;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'factories' => [
                AdapterInterface::class => AdapterServiceFactory::class,
                AdapterManager::class   => AdapterManagerFactory::class,
            ],
        ];
    }
}
