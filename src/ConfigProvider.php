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
            'abstract_factories' => [
                Container\AdapterAbstractServiceFactory::class,
            ],
            'aliases'            => [
                Adapter\AdapterInterface::class => Adapter\Adapter::class,
            ],
        ];
    }
}
