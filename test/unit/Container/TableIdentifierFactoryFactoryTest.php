<?php

declare(strict_types=1);

namespace PhpDbTest\Container;

use Laminas\ServiceManager\ServiceManager;
use PhpDb\Container\TableIdentifierFactoryFactory;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\TableIdentifierFactory;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[Group('unit')]
#[CoversMethod(TableIdentifierFactoryFactory::class, '__invoke')]
final class TableIdentifierFactoryFactoryTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function invokeCreatesFactoryWithConfiguredPrefix(): void
    {
        $container = new ServiceManager();
        $container->setService('config', [
            TableIdentifierFactory::class => [
                'prefix' => 'backup',
            ],
        ]);

        $factory = new TableIdentifierFactoryFactory();
        $result  = $factory($container);

        static::assertSame('backup', $result->getPrefix());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invokeCreatesFactoryWithConfiguredSeparator(): void
    {
        $container = new ServiceManager();
        $container->setService('config', [
            TableIdentifierFactory::class => [
                'prefix'    => 'backup',
                'separator' => '__',
            ],
        ]);

        $factory = new TableIdentifierFactoryFactory();
        $result  = $factory($container);

        static::assertSame('__', $result->getSeparator());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invokeCreatesFactoryWithConfiguredSeparatorWithoutPrefix(): void
    {
        $container = new ServiceManager();
        $container->setService('config', [
            TableIdentifierFactory::class => [
                'separator' => '__',
            ],
        ]);

        $factory = new TableIdentifierFactoryFactory();
        $result  = $factory($container);

        static::assertNull($result->getPrefix());
        static::assertSame('__', $result->getSeparator());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invokeCreatesFactoryWithoutPrefixWhenConfigIsEmpty(): void
    {
        $container = new ServiceManager();
        $container->setService('config', []);

        $factory = new TableIdentifierFactoryFactory();
        $result  = $factory($container);

        static::assertNull($result->getPrefix());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invokeCreatesFactoryWithoutPrefixWhenConfigServiceIsNull(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('config')->willReturn(true);
        $container->method('get')->with('config')->willReturn(null);

        $factory = new TableIdentifierFactoryFactory();
        $result  = $factory($container);

        static::assertNull($result->getPrefix());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invokeCreatesFactoryWithoutPrefixWhenContainerHasNoConfig(): void
    {
        $container = new ServiceManager();

        $factory = new TableIdentifierFactoryFactory();
        $result  = $factory($container);

        static::assertNull($result->getPrefix());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invokeCreatesFactoryWithoutPrefixWhenPrefixKeyIsAbsent(): void
    {
        $container = new ServiceManager();
        $container->setService('config', [
            TableIdentifierFactory::class => [],
        ]);

        $factory = new TableIdentifierFactoryFactory();
        $result  = $factory($container);

        static::assertNull($result->getPrefix());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invokeRejectsEmptyStringSeparatorFromConfig(): void
    {
        $container = new ServiceManager();
        $container->setService('config', [
            TableIdentifierFactory::class => [
                'separator' => '',
            ],
        ]);

        $factory = new TableIdentifierFactoryFactory();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$separator must be a valid table separator, empty string given');
        $factory($container);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invokeUsesDefaultSeparatorWhenSeparatorKeyIsAbsent(): void
    {
        $container = new ServiceManager();
        $container->setService('config', [
            TableIdentifierFactory::class => [
                'prefix' => 'backup',
            ],
        ]);

        $factory = new TableIdentifierFactoryFactory();
        $result  = $factory($container);

        static::assertSame('_', $result->getSeparator());
    }
}
