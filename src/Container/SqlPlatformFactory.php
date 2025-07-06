<?php

declare(strict_types=1);

namespace Laminas\Db\Container;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Platform\Platform;
use Laminas\Db\Sql\Platform\PlatformDecoratorInterface;
use Laminas\Db\Sql\SqlInterface;
use Laminas\Db\Sql\PreparableSqlInterface;
use Psr\Container\ContainerInterface;

final class SqlPlatformFactory
{
    /**
     * Create a Platform instance using the provided AdapterInterface.
     * This factory should only be used when a generic NON decorated Platform instance is needed.
     *
     * @param ContainerInterface $container
     * @return PlatformDecoratorInterface&PreparableSqlInterface&SqlInterface
     */
    public function __invoke(
        ContainerInterface $container
    ): PlatformDecoratorInterface&PreparableSqlInterface&SqlInterface {
        $adapter = $container->get(AdapterInterface::class);
        return new Platform($adapter);
    }
}
