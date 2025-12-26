<?php

declare(strict_types=1);

namespace PhpDb;

final class ConfigProvider
{
    public final const NAMED_ADAPTER_KEY = 'adapters';
    public function __invoke(): array
    {
        return [
            'dependencies'                  => $this->getDependencies(),
            Adapter\AdapterInterface::class => $this->getConfig(),
        ];
    }

    public function getConfig(): array
    {
        // supported configuration structure
        return [
            // Adapter\Adapter::class          => [],
            // Adapter\AdapterInterface::class => [],
            // self::NAMED_ADAPTER_KEY         => [
            //     Adapter\Adapter::class          => [],
            //     Adapter\AdapterInterface::class => [],
            //     'Custom\Name'                   => [],
            // ],
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
