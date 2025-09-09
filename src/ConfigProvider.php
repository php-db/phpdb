<?php

declare(strict_types=1);

namespace PhpDb;

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
                Adapter\AdapterInterface::class => Container\AdapterServiceFactory::class,
                Container\AdapterManager::class => Container\AdapterManagerFactory::class,
            ],
        ];
    }
}
