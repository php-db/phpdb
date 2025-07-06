<?php

declare(strict_types=1);

namespace PhpDb\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Platform\Platform;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
use PhpDb\Sql\SqlInterface;
use PhpDb\Sql\PreparableSqlInterface;
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
