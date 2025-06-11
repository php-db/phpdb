<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Driver\Feature;

use Laminas\Db\Adapter\Driver\DriverInterface;

abstract class AbstractFeature implements DriverFeatureInterface
{
    protected DriverInterface $driver;

    public function setDriver(DriverInterface $driver): DriverFeatureInterface
    {
        $this->driver = $driver;
        return $this;
    }
}
