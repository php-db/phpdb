<?php

declare(strict_types=1);

namespace Laminas\Db\Container;

use Laminas\ServiceManager\AbstractPluginManager;
use Override;
use Psr\Container\ContainerInterface;

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
        return;
    }
}

