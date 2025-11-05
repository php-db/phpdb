<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Driver\Feature;

use Override;
use PhpDb\Adapter\Driver\DriverInterface;

abstract class AbstractFeature implements DriverFeatureInterface
{
    protected DriverInterface $driver;

    #[Override]
    public function setDriver(DriverInterface $driver): DriverFeatureInterface
    {
        $this->driver = $driver;
        return $this;
    }
}
