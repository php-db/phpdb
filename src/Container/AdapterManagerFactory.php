<?php

declare(strict_types=1);

namespace PhpDb\Container;

use Psr\Container\ContainerInterface;

final class AdapterManagerFactory
{
    public function __invoke(ContainerInterface $container): AdapterManager
    {
        return new AdapterManager($container);
    }
}
