<?php

declare(strict_types=1);

namespace PhpDbTest;

use PhpDb\Adapter;
use PhpDb\ConfigProvider;
use PhpDb\Container;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    /**
     * @phpstan-var array{
     *      'dependencies': array{
     *          abstract_factories: list<class-string>,
     *          aliases: array<class-string, class-string>,
     *          factories: array<class-string, class-string>,
     *      }
     * }
     * */
    private array $config = [
        Adapter\AdapterInterface::class => [],
        'dependencies'                  => [
            'abstract_factories' => [
                Container\AbstractAdapterInterfaceFactory::class,
            ],
            'aliases'            => [
                Adapter\AdapterInterface::class => Adapter\Adapter::class,
            ],
            'factories'          => [
                Adapter\Adapter::class => Container\AdapterInterfaceFactory::class,
            ],
        ],
    ];

    public function testInvocationProvidesDependencyConfiguration(): void
    {
        self::assertEquals($this->config, (new ConfigProvider())());
    }
}
