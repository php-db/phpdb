<?php

declare(strict_types=1);

namespace Laminas\Db\Container;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Driver;
use Laminas\Db\Adapter\Platform;
use Laminas\Db\Adapter\Profiler;
use Laminas\Db\Adapter\Exception;
use Laminas\Db\Metadata;
use Laminas\Db\ResultSet\ResultSetInterface;
use Laminas\ServiceManager\AbstractPluginManager;
use Override;
use Psr\Container\ContainerInterface;

use function get_debug_type;
use function sprintf;

final class AdapterManager extends AbstractPluginManager
{
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
        if (is_array($instance)) {
            // If the instance is an array, we assume it's a configuration array
            // and do not validate it as a service instance.
            return;
        }
        $validate = match(true) {
            $instance instanceof AdapterInterface,
            $instance instanceof Driver\DriverInterface,
            $instance instanceof Driver\ResultInterface,
            $instance instanceof Driver\ConnectionInterface,
            $instance instanceof Platform\PlatformInterface,
            $instance instanceof Profiler\ProfilerInterface,
            $instance instanceof ResultSetInterface,
            $instance instanceof Metadata\MetadataInterface => true,
            default => throw new Exception\RuntimeException(sprintf(
                'AdapterManager can not create an instance of %s',
                get_debug_type($instance)
            )),
        };
    }
}

