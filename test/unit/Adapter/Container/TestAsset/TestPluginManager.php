<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Container\TestAsset;

use Laminas\ServiceManager\AbstractPluginManager;

class TestPluginManager extends AbstractPluginManager
{
    public function validate(mixed $instance): void
    {
    }
}
