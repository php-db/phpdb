<?php

declare(strict_types=1);

namespace PhpDb;

final class ConfigProvider
{
    public const NAMED_ADAPTER_KEY = 'adapters';

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
                Container\AbstractAdapterInterfaceFactory::class,
            ],
            'aliases'            => [
                Adapter\AdapterInterface::class => Adapter\Adapter::class,
            ],
            'factories'          => [
                Adapter\Adapter::class => Container\AdapterInterfaceFactory::class,
            ],
        ];
    }
}
