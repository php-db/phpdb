<?php

declare(strict_types=1);

namespace PhpDbTest;

use PhpDb\Adapter;
use PhpDb\ConfigProvider;
use PhpDb\Container\AbstractAdapterInterfaceFactory;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    /** @phpstan-var array{'dependencies': array{abstract_factories: list<class-string>, aliases: array<class-string, class-string>}} */
    private array $config = [
        Adapter\AdapterInterface::class => [],
        'dependencies'                  => [
            'abstract_factories' => [
                AbstractAdapterInterfaceFactory::class,
            ],
            'aliases'            => [
                Adapter\AdapterInterface::class => Adapter\Adapter::class,
            ],
        ],
    ];

    public function testInvocationProvidesDependencyConfiguration(): void
    {
        self::assertEquals($this->config, (new ConfigProvider())());
    }
}
