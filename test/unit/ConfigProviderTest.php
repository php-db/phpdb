<?php

namespace LaminasTest\Db;

use Laminas\Db\Adapter;
use Laminas\Db\ConfigProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Zend\Db\Adapter\AdapterInterface;

class ConfigProviderTest extends TestCase
{
    /** @var array<string, array<array-key, string>> */
    private array $config = [
        'abstract_factories' => [
            Adapter\AdapterAbstractServiceFactory::class,
        ],
        'factories'          => [
            Adapter\AdapterInterface::class => Adapter\AdapterServiceFactory::class,
        ],
        'aliases'            => [
            Adapter\Adapter::class          => Adapter\AdapterInterface::class,
        ],
    ];

    public function testProvidesExpectedConfiguration(): ConfigProvider
    {
        $provider = new ConfigProvider();
        self::assertEquals($this->config, $provider->getDependencyConfig());
        return $provider;
    }

    #[Depends('testProvidesExpectedConfiguration')]
    public function testInvocationProvidesDependencyConfiguration(ConfigProvider $provider): void
    {
        self::assertEquals(['dependencies' => $provider->getDependencyConfig()], $provider());
    }
}
