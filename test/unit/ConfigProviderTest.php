<?php

declare(strict_types=1);

namespace PhpDbTest;

use PhpDb\Adapter;
use PhpDb\ConfigProvider;
use PhpDb\Container;
use PhpDb\Sql;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigProvider::class)]
#[Group('unit')]
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
        'dependencies' => [
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
        ],
    ];

    #[Test]
    public function getDependenciesIsCallableAsPartOfThePublicApi(): void
    {
        static::assertEquals($this->config['dependencies'], (new ConfigProvider())->getDependencies());
    }

    #[Test]
    public function invocationProvidesDependencyConfiguration(): void
    {
        static::assertEquals($this->config, (new ConfigProvider())());
    }
}
