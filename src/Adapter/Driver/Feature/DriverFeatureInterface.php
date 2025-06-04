<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Driver\Feature;

use Laminas\Db\Adapter\Driver\DriverInterface;

interface DriverFeatureInterface
{
    public function setDriver(DriverInterface $driver): void;
}
