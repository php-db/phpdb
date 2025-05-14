<?php

declare(strict_types=1);

namespace Laminas\Db\Container;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Metadata\MetadataInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Psr\Container\ContainerInterface;

final class MetadataFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): MetadataInterface
    {
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
