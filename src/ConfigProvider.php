<?php

declare(strict_types=1);

namespace PhpDb;

final readonly class ConfigProvider
{
    public const string NAMED_ADAPTER_KEY = 'adapters';

    /**
     * @return array{
     *     abstract_factories: list<class-string>,
     *     aliases: array<class-string, class-string>,
     *     factories: array<class-string, class-string>,
     * }
     */
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

    /**
     * @return array{dependencies: array{
     *     abstract_factories: list<class-string>,
     *     aliases: array<class-string, class-string>,
     *     factories: array<class-string, class-string>,
     * }}
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }
}
