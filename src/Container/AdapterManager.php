<?php

declare(strict_types=1);

namespace Laminas\Db\Container;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Driver;
use Laminas\Db\Adapter\Platform;
use Laminas\Db\Adapter\Profiler;
use Laminas\Db\Adapter\Exception;
use Laminas\Db\Metadata;
use Laminas\ServiceManager\AbstractPluginManager;
use Override;
use Psr\Container\ContainerInterface;

use function array_replace_recursive;
use function get_debug_type;
use function sprintf;

final class AdapterManager extends AbstractPluginManager
{

    // private const CONFIG = [
    //     'factories' => [
    //         Driver\DriverInterface::class => Driver\DriverServiceDelegator::class,
    //         Driver\ConnectionInterface::class => Driver\ConnectionServiceDelegator::class,
    //         Platform\PlatformInterface::class => Platform\PlatformServiceDelegator::class,
    //         Profiler\ProfilerInterface::class => Profiler\ProfilerServiceDelegator::class,
    //         Metadata\MetadataInterface::class => Metadata\MetadataServiceDelegator::class,
    //     ],
    // ];

    public function __construct(
        ContainerInterface $container,
        array $config = [],
    ) {
        parent::__construct($container, $config);
    }

    /**
     * validate the service types this manager can create
     */
    #[Override]
    public function validate(mixed $instance): void
    {
        if ($instance instanceof AdapterInterface) {
            return;
        }

        if ($instance instanceof Driver\DriverInterface) {
            return;
        }

        if ($instance instanceof Driver\ConnectionInterface) {
            return;
        }

        if ($instance instanceof Platform\PlatformInterface) {
            return;
        }

        if ($instance instanceof Profiler\ProfilerInterface) {
            return;
        }

        if ($instance instanceof Metadata\MetadataInterface) {
            return;
        }

        throw new Exception\RuntimeException(sprintf(
            'AdapterManager expects an instance of %s, %s, %s, %s, or %s; received %s',
            AdapterInterface::class,
            Driver\DriverInterface::class,
            Driver\ConnectionInterface::class,
            Platform\PlatformInterface::class,
            Profiler\ProfilerInterface::class,
            Metadata\MetadataInterface::class,
            get_debug_type($instance)
        ));
    }
}

