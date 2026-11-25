<?php

declare(strict_types=1);

namespace PhpDb;

final readonly class ConfigProvider
{
    public const string NAMED_ADAPTER_KEY = 'adapters';

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
                Adapter\Adapter::class            => Container\AdapterInterfaceFactory::class,
                Sql\TableIdentifierFactory::class => Container\TableIdentifierFactoryFactory::class,
            ],
        ];
    }

    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }
}
