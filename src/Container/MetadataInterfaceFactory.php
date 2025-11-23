<?php

declare(strict_types=1);

namespace PhpDb\Container;

use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Metadata\MetadataInterface;
use Psr\Container\ContainerInterface;

use function sprintf;

/** todo this factory may move in 0.4.x */
final class MetadataInterfaceFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null
    ): MetadataInterface {
        if (! $container->has(AdapterInterface::class)) {
            throw new ServiceNotFoundException(sprintf(
                'Service "%s" not found in container',
                AdapterInterface::class
            ));
        }

        return new $requestedName(
            $container->get(AdapterInterface::class),
        );
    }
}
