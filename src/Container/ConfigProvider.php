<?php

declare(strict_types=1);

namespace PhpDb\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\AdapterServiceFactory;

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
